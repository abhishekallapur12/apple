<?php

/**
 * MySQL CacheResource
 * CacheResource Implementation based on the Custom API to use
 * MySQL as the storage resource for Smarty's output caching.
 * Table definition:
 * <pre>CREATE TABLE IF NOT EXISTS `output_cache` (
 *   `id` CHAR(40) NOT NULL COMMENT 'sha1 hash',
 *   `name` VARCHAR(250) NOT NULL,
 *   `cache_id` VARCHAR(250) NULL DEFAULT NULL,
 *   `compile_id` VARCHAR(250) NULL DEFAULT NULL,
 *   `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *   `expire` DATETIME NULL DEFAULT NULL COMMENT 'NULL = never expires',
 *   `content` LONGTEXT NOT NULL,
 *   PRIMARY KEY (`id`),
 *   INDEX(`name`),
 *   INDEX(`cache_id`),
 *   INDEX(`compile_id`),
 *   INDEX(`modified`),
 *   INDEX(`expire`)
 * ) ENGINE = InnoDB;</pre>
 *
 * Upgrading an existing table:
 * <pre>ALTER TABLE `output_cache` ADD `expire` DATETIME NULL DEFAULT NULL, ADD INDEX(`expire`);</pre>
 *
 * @package CacheResource-examples
 * @author  Rodney Rehm
 */
class Smarty_CacheResource_Mysql extends Smarty_CacheResource_Custom
{
    /**
     * @var \PDO
     */
    protected $db;

    /**
     * @var \PDOStatement
     */
    protected $fetch;

    /**
     * @var \PDOStatement
     */
    protected $fetchTimestamp;

    /**
     * @var \PDOStatement
     */
    protected $save;

    /**
     * Smarty_CacheResource_Mysql constructor.
     *
     * @throws \SmartyException
     */
    public function __construct()
    {
        try {
            // Login details come from server settings, never from the code.
            $user = getenv('SMARTY_DB_USER');
            if ($user === false || $user === '') {
                throw new SmartyException('Mysql Resource failed: set SMARTY_DB_USER and SMARTY_DB_PASSWORD');
            }
            $this->db = new PDO(
                getenv('SMARTY_DB_DSN') ?: 'mysql:dbname=test;host=127.0.0.1',
                $user,
                (string)getenv('SMARTY_DB_PASSWORD'),
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            throw new SmartyException('Mysql Resource failed: ' . $e->getMessage());
        }
        $this->fetch = $this->db->prepare('SELECT modified, content FROM output_cache WHERE id = :id');
        $this->fetchTimestamp = $this->db->prepare('SELECT modified FROM output_cache WHERE id = :id');
        $this->save = $this->db->prepare(
            'REPLACE INTO output_cache (id, name, cache_id, compile_id, expire, content)
            VALUES  (:id, :name, :cache_id, :compile_id,
                     IF(:has_expire, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :expire SECOND), NULL), :content)'
        );
    }

    /**
     * fetch cached content and its modification time from data source
     *
     * @param string  $id         unique cache content identifier
     * @param string  $name       template name
     * @param string  $cache_id   cache id
     * @param string  $compile_id compile id
     * @param string  $content    cached content
     * @param integer $mtime      cache modification timestamp (epoch)
     *
     * @return void
     */
    protected function fetch($id, $name, $cache_id, $compile_id, &$content, &$mtime)
    {
        $this->fetch->execute(array('id' => $id));
        $row = $this->fetch->fetch();
        $this->fetch->closeCursor();
        if ($row) {
            $content = $row[ 'content' ];
            $mtime = strtotime($row[ 'modified' ]);
        } else {
            $content = null;
            $mtime = null;
        }
    }

    /**
     * Fetch cached content's modification timestamp from data source
     *
     * @note implementing this method is optional. Only implement it if modification times can be accessed faster than
     *       loading the complete cached content.
     *
     * @param string $id         unique cache content identifier
     * @param string $name       template name
     * @param string $cache_id   cache id
     * @param string $compile_id compile id
     *
     * @return integer|boolean timestamp (epoch) the template was modified, or false if not found
     */
    protected function fetchTimestamp($id, $name, $cache_id, $compile_id)
    {
        $this->fetchTimestamp->execute(array('id' => $id));
        $mtime = strtotime($this->fetchTimestamp->fetchColumn());
        $this->fetchTimestamp->closeCursor();
        return $mtime;
    }

    /**
     * Save content to cache
     *
     * @param string       $id         unique cache content identifier
     * @param string       $name       template name
     * @param string       $cache_id   cache id
     * @param string       $compile_id compile id
     * @param integer|null $exp_time   seconds till expiration time in seconds or null
     * @param string       $content    content to cache
     *
     * @return boolean      success
     */
    protected function save($id, $name, $cache_id, $compile_id, $exp_time, $content)
    {
        $this->save->execute(
            array('id' => $id,
                  'name' => $name,
                  'cache_id' => $cache_id,
                  'compile_id' => $compile_id,
                  // a lifetime below 0 (or none) means "never expires"
                  'has_expire' => ($exp_time !== null && $exp_time >= 0) ? 1 : 0,
                  'expire' => ($exp_time !== null && $exp_time >= 0) ? (int)$exp_time : 0,
                  'content' => $content,)
        );
        return !!$this->save->rowCount();
    }

    /**
     * Delete content from cache
     *
     * @param string       $name       template name
     * @param string       $cache_id   cache id
     * @param string       $compile_id compile id
     * @param integer|null $exp_time   seconds till expiration or null
     *
     * @return integer      number of deleted caches
     */
    protected function delete($name, $cache_id, $compile_id, $exp_time)
    {
        // delete the whole cache
        if ($name === null && $cache_id === null && $compile_id === null && $exp_time === null) {
            // returning the number of deleted caches would require a second query to count them
            $query = $this->db->query('TRUNCATE TABLE output_cache');
            return -1;
        }
        // build the filter
        $where = array();
        // equal test name
        if ($name !== null) {
            $where[] = 'name = ' . $this->db->quote($name);
        }
        // equal test compile_id
        if ($compile_id !== null) {
            $where[] = 'compile_id = ' . $this->db->quote($compile_id);
        }
        // clear only caches that have run out of time (Smarty::CLEAR_EXPIRED is -1)
        if ($exp_time === Smarty::CLEAR_EXPIRED) {
            $where[] = 'expire IS NOT NULL AND expire < CURRENT_TIMESTAMP';
        } // range test expiration time
        elseif ($exp_time !== null) {
            $where[] = 'modified < DATE_SUB(NOW(), INTERVAL ' . intval($exp_time) . ' SECOND)';
        }
        // equal test cache_id and match sub-groups
        if ($cache_id !== null) {
            $where[] =
                '(cache_id = ' .
                $this->db->quote($cache_id) .
                ' OR cache_id LIKE ' .
                $this->db->quote($cache_id . '|%') .
                ')';
        }
        // run delete query
        $query = $this->db->query('DELETE FROM output_cache WHERE ' . join(' AND ', $where));
        return $query->rowCount();
    }
}
