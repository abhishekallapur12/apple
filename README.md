# Apple Project

A Python project with an automated CI/CD pipeline built with GitHub Actions.

<!-- Test branch for validating PR CI/CD pipeline -->

## CI/CD Pipeline

The pipeline runs automatically on:
- **Pull Requests targeting `main`**: Runs unit tests (CI), then runs preview/staging deployment verification (CD).
- **Pushes to `main`**: Runs unit tests (CI), then deploys to production (CD).
- **Manual Trigger**: Can be run manually anytime via GitHub Actions **Run workflow** (`workflow_dispatch`).

### Jobs
1. **Run Tests (CI)**: Sets up Python 3.12 and runs `python -m unittest discover -v`.
2. **Deployment (CD)**: Runs only after CI passes (`needs: test`), targets the appropriate environment based on the event (PR staging vs. main production), and executes the deployment.
