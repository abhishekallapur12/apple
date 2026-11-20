# Simple Code Reviewer — Rules (every agent reads this first)

The goal is a **code review**: find the real bugs, errors and risks in the project, and explain
**how to fix each one** — in words that anyone can follow.

## 1. Write for everyone
The report is read by managers, owners, testers and developers. Everything — including every issue and
every file location — must make sense to someone who has never written code.
- Use everyday words and short sentences (aim for under 20 words).
- The first time you use a technical word, explain it in brackets, e.g. "SQL injection (a trick where an
  attacker types database commands into a form)". Spell out abbreviations.
- Explain **what it means for people**: users, customers, the business, the team.
- **File locations are part of the normal text, never a separate "for developers" line.** Always say
  what the place is in plain words, then the file and line, e.g.
  "In the part that saves approvals (`backend/src/Api/Program.cs`, line 1913)".
- When you name a function or setting, say what it does in brackets the first time, e.g.
  "`ApprovedBy` (the field that records who approved)".
- No long code excerpts. A single short line is allowed only when it makes the fix clearer.
- Be calm and factual. No blame, no people's names.

## 2. Each agent has its own file and its own topic
Four agents run **at the same time**, each writing **only its own file**. The report-writer runs last and
writes the summary. All files are in the `simple-review/` folder:

| File | Owner | Topic (only this) | Issue IDs |
|---|---|---|---|
| `REVIEWED-OUTCOMES.md` | report-writer | Summary, all issues table, action plan, links to the other files | — |
| `1-bugs.md` | bug-checker | Bugs and errors in the code, and coding standards (SOLID etc.) | B-01… |
| `2-risks.md` | risk-checker | Security, speed, testing and reliability | R-01… |
| `3-changes-pr.md` | pr-reviewer | The latest changes (PR review) | C-01… |
| `4-about-project.md` | scanner | The project and what it is built with (versions) | — |

How to write your file, so nothing clashes:
1. **Read** your own file first (the Write tool needs this), then write **only your own file** with the
   **Write** tool, as one complete file. Never write, edit or delete
   any other file — not the other agents' files, and no project file.
2. Your file may already contain an old result or a "running" note — replace all of it.
3. Start your file with:
   ```markdown
   # <title of your file> — <project folder name>
   Date: <YYYY-MM-DD> · Part of the code review · Start with the [reviewed outcomes](REVIEWED-OUTCOMES.md)
   ```
4. **Stay inside your topic.** If you notice something that belongs to another topic, leave it out —
   another agent covers it. (E.g. the bug-checker does not report security holes.)
5. **Each issue is explained in full only once**, in your own file. Do not repeat the same problem in
   several issues.
6. End your file with a short `### What we could not check` list (or "Nothing important.").

## 3. Only look, never change
- Do **not** change any project file. The only files anyone writes are the five files above.
- Do not build, install, run tests, run the app or connect to a database.
- Shell commands: read-only only (`git log`, `git ls-files`, `git status`, `git diff`, `git show`,
  `git branch`, `git merge-base`, `git merge-tree`, `git rev-parse`, `git rev-list`, `git symbolic-ref`,
  `wc -l`, `ls`, `sort`, `uniq`, `head`, `date`). Never run git commands that change branches, files or
  history (fetch, pull, merge, rebase, checkout, reset, commit…). Never write files with the shell
  (no `>`, `>>`, `tee`, `sed -i`, `rm`).

## 4. Versions — from the project files only, no online checks
- Versions come only from files in the project (e.g. `package.json`, `*.csproj`, `pom.xml`,
  `composer.json`, `requirements.txt`, `pyproject.toml`, `go.mod`, `Dockerfile`).
- Do **not** look anything up online. Never say a version is "old", "newest", "unsupported" or "end of
  life" from memory.

## 5. Keep it short — focus on what matters
- Max **12 full issues** per file, most serious first. If you found more, keep the most important.
- ⚪ **Nice to have** items do **not** get the full format: list them at the end of your issues as one
  line each under `### Small tidy-ups` — "- **<ID> — <title>** — <where>: <fix in a few words>".
- Do not pad: no long lists of things that are fine, no repeating the same point in different words.

## 6. Secrets stay secret
- Never open `.env`, `.env.*`, `*.pem`, `*.key`, `*.pfx`, `*.p12`, `secrets.json` or similar secret files.
- If a password, key or token is written in the code, report **where** it is, never the value.

## 7. Only real issues
- A search hit is not an issue until you have read the code and confirmed it.
- Trace it: what input or situation makes it go wrong, and what exactly happens then?
- If you are not sure, say so ("Needs checking") or leave it out. A few real issues are better than many
  maybe-issues.

## 8. Finding your way (the parallel agents)
You run at the same time as the others, so nobody gives you a file list. Look around quickly yourself:
`git ls-files` (or Glob), skip `node_modules`, `bin`, `obj`, `dist`, `build`, `vendor`, `.venv`, `.git`,
`.claude`, `simple-review`, generated and minified files. Find the starting points (where the app starts,
where requests come in), the largest files (`wc -l`) and the most-changed files
(`git log --name-only --pretty=format: | sort | uniq -c | sort -rn | head -20`). Then go deep.

## 9. How to write an issue

```markdown
### B-01 — <plain title anyone understands, e.g. "Customers can see each other's orders">
**How serious:** 🔴 Fix now  ·  **Type:** Bug / Error handling / Security / Speed / …  ·  **How sure:** Certain
**Where:** <plain name of the place> (`path/to/file.ext`, lines 10–25; also `other/file.ext`, line 40)
**What is wrong:** 1–3 plain sentences describing the problem.
**When it happens:** a simple example, e.g. "If two people press Approve at the same moment, …".
**What could happen:** the real-world effect on users, customers or the business.
**How to fix it:**
1. Concrete step in plain words, naming the place to change (e.g. "In the approve step, save the
   signed-in person's name instead of the fixed text 'Human Reviewer'.").
2. …
**How to check the fix worked:** what to try or test afterwards.
**Size of the job:** small (under a day) / medium (a few days) / large (weeks)
```

**How sure:** Certain · Likely · Needs checking.

How serious:
- 🔴 **Fix now** — could be misused or could break or lose data right now.
- 🟠 **Fix soon** — serious, but needs some condition, or likely to cause trouble in normal use.
- 🟡 **Plan to fix** — a real weakness with smaller impact, or it makes future changes slow and risky.
- ⚪ **Nice to have** — tidying up and small improvements.

## 10. Short replies
When you finish, reply to the master in **5 lines or fewer**: issue counts by seriousness (if any) and
the most important point. Do not paste your file into the reply.
