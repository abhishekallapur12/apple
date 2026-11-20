<?php
/**
 * Small smoke test for the archived Smarty engine.
 * Run with: php tests/smoke_test.php   (exit code 0 = all checks passed)
 */
require dirname(__DIR__) . '/libs/Smarty.class.php';

$failures = 0;

function check($label, $actual, $expected)
{
    global $failures;
    if ($actual === $expected) {
        echo "ok   - $label\n";
        return;
    }
    $failures++;
    echo "FAIL - $label\n       expected: " . var_export($expected, true) . "\n       actual:   " . var_export($actual, true) . "\n";
}

function make_smarty()
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'smarty-smoke-' . getmypid();
    $smarty = new Smarty();
    $smarty->setCompileDir($dir . DIRECTORY_SEPARATOR . 'compile');
    $smarty->setCacheDir($dir . DIRECTORY_SEPARATOR . 'cache');
    return $smarty;
}

$smarty = make_smarty();
$smarty->assign('name', 'World');
check('prints a variable', $smarty->fetch('string:Hello {$name}!'), 'Hello World!');

$smarty->assign('items', array('a', 'b', 'c'));
check('loops with foreach', $smarty->fetch('string:{foreach $items as $i}[{$i}]{/foreach}'), '[a][b][c]');

$smarty->assign('html', '<b>"x"</b>');
check('escape modifier makes HTML harmless', $smarty->fetch('string:{$html|escape}'), '&lt;b&gt;&quot;x&quot;&lt;/b&gt;');

$escaping = make_smarty();
$escaping->escape_html = true;
$escaping->assign('html', '<script>alert(1)</script>');
check('automatic escaping (escape_html) is applied', $escaping->fetch('string:{$html}'), '&lt;script&gt;alert(1)&lt;/script&gt;');
check('nofilter switches automatic escaping off', $escaping->fetch('string:{$html nofilter}'), '<script>alert(1)</script>');

check('if / else works', $smarty->fetch('string:{if 2 > 1}yes{else}no{/if}'), 'yes');

echo $failures === 0 ? "\nAll checks passed.\n" : "\n$failures check(s) failed.\n";
exit($failures === 0 ? 0 : 1);
