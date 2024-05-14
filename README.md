# Composer auto update action

This action sends a message to an AWS SNS Topic to trigger a composer update workflow in each bedrock.

## Input: Environment variables

### `INPUT_TRIGGERED_BY`

**Required** The repo that triggers the action.

### `REF`

**Required** The REF that triggers the action, it could be `refs/heads/dev` or `*.*.*`.

### `AWS_ACCESS_KEY_ID`

**Required** AWS Access Key Id from the dev and staging environment

### `AWS_ACCESS_KEY`

**Required** AWS Access Key from the dev and staging environment.

### `AWS_SNS_ARN_DEV`

**Required** ARN of the AWS SNS Topic to publish for the dev repositories.

### `AWS_SNS_ARN_STAGING`

**Required** ARN of the AWS SNS Topic to publish for the staging repositories.

## Steps to configure this process

## 1. Configure bedrock repos

All Bedrock repos should contain two workflows:

* `trigger-bedrock-updates.yml`: This workflow is triggered by a AWS Lambda Function. It runs the `composer update` command and open a pull request to the `default` branch.
* `auto-merge.yml`: This workflow is triggered by the `autoupdate.yml` workflow. It merges the pull request to the `target` branch.

Those workflows would use reusable workflows to avoid duplication.

See: [Pressbooks Reusable Workflows](https://github.com/pressbooks/reusable-workflows) for more information.

## 2. Configure plugin repos

All plugin repos should contain a step that triggers the `composer-autoupdate-bedrock` action, this step will publish a message to an AWS SNS Topic to trigger the update process in all bedrock repos.

This step should be added as one of the last steps in the `*.yml` workflow that contains tests, code coverage, etc...

> The AWS ACCESS Token should have the permission to publish to the SNS Topics.

> if: github.ref == 'refs/heads/dev' it's used to avoid triggering the action in other branches for example this would trigger only when the dev branch is updated.

```yaml
jobs:
  # other tasks... like linting, testing, etc.
  trigger_bedrock_updates:
    if: github.ref == 'refs/heads/dev'
    runs-on: ubuntu-latest
    steps:
        - name: Trigger Bedrock Composer Update
          uses: pressbooks/composer-autoupdate-bedrock@main
          env:
              AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
              AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
              AWS_SNS_ARN_DEV: ${{ secrets.AWS_SQS_ARN_DEV }}
              AWS_SNS_ARN_STAGING: ${{ secrets.AWS_SQS_ARN_STAGING }}
              INPUT_TRIGGERED_BY: ${{ github.repository }}
              REF: ${{ github.ref }}

```

### Development

In order to submit a new version of this action, you need to create a new tag in the format `vX` and push it to the repository or update an existing tag.

You should compile the code before pushing the tag, to do that you need to run the following command:

```bash
ncc build index.js --license licenses.txt
```

This will create a new folder called `dist` with the compiled code bundling node_modules dependencies.

#### Pushing a new version

```bash
git commit -m "feat: new version"
git tag -a -m "feat: feature description" v1
git push --follow-tags
```
#### Using the new version in a plugin

```yaml
  uses: pressbooks/composer-autoupdate-bedrock@vX    
```

or if always using the latest version

```yaml
  uses: pressbooks/composer-autoupdate-bedrock@main   
```
