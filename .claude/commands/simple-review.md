---
description: Simple code review — four helper agents work at the same time to find the bugs, errors and risks in the project (with how to fix each one), review the latest changes (PR review) and describe the project, each in its own file; then a summary file links everything together. Plain language anyone can understand.
argument-hint: "[--base <branch>] [--last <n>] [--one-by-one] [--fresh]"
allowed-tools: Read, Glob, Grep, Write, Bash, Agent, Task
---

You are the **master**. You do not read or review the code yourself — you send the work to the helper
agents, keep track, and talk to the user. Arguments: `$ARGUMENTS`
- `--base <branch>` = the main branch the PR review compares against.
- `--last <n>` = how many recent saved changes to review when there is no branch to compare (default 10).
- `--fresh` = describe the project again even if nothing changed since the last run.
- `--one-by-one` = run the four helpers one after another instead of at the same time (for slower
  computers or small usage plans).

Read `.claude/simple-review/rules.md` first. Talk to the user in plain, friendly words.

**Each helper has its own file**, so they can work at the same time without clashing (rule 2):

| Helper | Writes |
|---|---|
| bug-checker | `simple-review/1-bugs.md` |
| risk-checker | `simple-review/2-risks.md` |
| pr-reviewer | `simple-review/3-changes-pr.md` |
| scanner | `simple-review/4-about-project.md` |
| report-writer (last) | `simple-review/REVIEWED-OUTCOMES.md` |

## Step 0 — Prepare the files
Get today's date (`date +%Y-%m-%d`) and the current commit ID (`git rev-parse HEAD`; "none" if this is
not a git repository).

**Can the project description be reused?** If `simple-review/4-about-project.md` exists, its last line is
`<!-- based-on: <ID> -->` with the **same** commit ID, the ID is not "none", and `--fresh` was not given,
then **reuse it**: do not clear it and do not run the scanner. (Same commit = the project has not changed,
so the description would be the same.) Otherwise the scanner runs as normal.

Write each of the other files above (and `4-about-project.md` when it is not reused) with only this line,
so old results from an earlier run are cleared:

```markdown
_(running…)_
```

If an old `simple-review/REPORT.md` or `simple-review/SUMMARY.md` exists (from an older version of this
kit), Write each with the single line `This review now lives in [REVIEWED-OUTCOMES.md](REVIEWED-OUTCOMES.md).`

## Step 1 — Run the four helpers at the same time
Tell the user: "Step 1 of 2: four helpers are now reviewing the project at the same time — bugs, risks,
latest changes and the project itself. This takes a while…"

Launch **bug-checker**, **risk-checker**, **pr-reviewer** and **scanner** **in one message** (four Agent
calls together), so they run in parallel, or only the first three when the project description is
reused (tell the user: "The project has not changed since the last run, so the project description is
reused."). Give each one today's date and the project folder name; give the scanner the commit ID too. Give
pr-reviewer the branch from `--base` and the number from `--last` if given; otherwise "find the main
branch yourself; if there is none, review the last 10 saved changes".

With `--one-by-one`: launch them one after another in the same order instead, and tell the user which one
is running ("Helper 1 of 4: looking for bugs and errors…").

## Step 2 — Check the four files
For each file that was run, check (Grep) that `_(running…)_` is gone. If it is still there, or the helper
failed, run **only that helper** once more (several failed helpers can be re-run together). If it fails
again, Write that file with `_This part could not be completed._` and go on.

## Step 3 — Write the summary
Tell the user: "Step 2 of 2: double-checking the serious issues and writing the summary…"
Launch **report-writer**. It writes `simple-review/REVIEWED-OUTCOMES.md` itself.
- If its reply starts with `SAVE-FOR-ME:` (Claude Code did not let it save the file), Write the complete
  text from its reply into `simple-review/REVIEWED-OUTCOMES.md` **exactly as given** — do not change it.
- Then check that `_(running…)_` is gone from the file; if not, run report-writer once more.

## Finish
Reply in plain words (max 12 lines):
- What the project is, in one sentence, and its overall condition
- Issues: 🔴 Fix now / 🟠 Fix soon / 🟡 Plan to fix / ⚪ Nice to have (from the summary)
- The top 3 issues, one plain line each
- Latest changes: one line with the verdict (or "no changes to review")
- "Start with `simple-review/REVIEWED-OUTCOMES.md` — it links to every detail in the other four files."
