# Composer auto update action

This action triggers the composer update workflow in each bedrock defined in the index.js file.

## Inputs

### `triggered-by`

**Required** The repo that triggers the action.

### `token`

**Required** Personal access token with write access to the organization repos.

## Steps to configure this process

## 1. Configure bedrock repos

All Bedrock repos should contain two workflows:

* `autoupdate.yml`: This workflow is triggered by the `composer-autoupdate-bedrock` action. It runs the `composer update` command and open a pull request to the `default` branch.
* `automerge.yml`: This workflow is triggered by the `autoupdate.yml` workflow. It merges the pull request to the `dev` branch.

### autoupdate.yml

```yaml
name: Pressbooks composer update

on:
 workflow_dispatch:

jobs:
  composer_update_job:
    uses: pressbooks/composer-autoupdate-bedrock/.github/workflows/auto-update.yml@v1
    secrets: inherit
```

### autoupdate.yml

```yaml
name: Pressbooks auto merge
on:
  pull_request_target:
    types: [ opened ]

jobs:
  automerge:
    uses: pressbooks/composer-autoupdate-bedrock/.github/workflows/auto-merge.yml@v1
    secrets: inherit
```

## 2. Configure plugin repos

All plugin repos should contain an step that triggers the `composer-autoupdate-bedrock` action, this step will trigger the update process in all bedrock repos defined in the index.js file on this action.

This step should be added as one of the last steps in the `*.yml` workflow that contains tests, code coverage, etc...

> The token should be a valid personal access token with write access to the organization repos.

> if: github.ref == 'refs/heads/dev' it's used to avoid triggering the action in other branches for example this would trigger only when the dev branch is updated.

```yaml
jobs:
  # other tasks... like linting, testing, etc.
  trigger_bedrock_updates:
    if: github.ref == 'refs/heads/dev'
    runs-on: ubuntu-latest
    steps:
      - name: Trigger Bedrock Composer Update
        uses: pressbooks/composer-autoupdate-bedrock@v1.6
        with:
          triggered-by: ${{ github.repository }}
          token: ${{ secrets.ORGANIZATION_PAT }}

```
