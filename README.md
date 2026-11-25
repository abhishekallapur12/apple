v2

## Run the CI workflow

The CI workflow runs automatically when you open or update a pull request targeting `main`. It also runs on pushes directly to `main`.

To trigger it with a pull request:

1. Push a commit to a branch.
2. Open a pull request from that branch into `main`.
3. Open the **Actions** tab or the pull request’s **Checks** section to watch the tests.

The workflow runs the Python unit tests with `python -m unittest discover -v`.
