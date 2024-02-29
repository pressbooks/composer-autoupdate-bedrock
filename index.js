const fetch = require('node-fetch');
const core = require('@actions/core');
const { Octokit: ActionOctokit } = require("@octokit/action");
const { Octokit: RestOctokit } = require("@octokit/rest");

const isGitHubActions = process.env.GITHUB_ACTION || false;
const owner = 'pressbooks';
const bedrockBranchTarget = {
  'dev': ['dev', 'lti-development'],
  'staging': ['staging'],
  'production': ['production']
};

const trigger = isGitHubActions ? core.getInput('triggered-by') : process.env.INPUT_TRIGGERED_BY || 'default-trigger';
const token = isGitHubActions ? core.getInput('token') : process.env.INPUT_TOKEN || 'default-token';
let branch = isGitHubActions ? core.getInput('branch') : process.env.INPUT_BRANCH || 'refs/heads/dev';
branch === 'refs/heads/production' ? branch = 'staging' : branch = 'dev';

const actionOctokit = new ActionOctokit({
  auth: token,
  request: {
    fetch: fetch,
  },
});

const restOctokit = new RestOctokit({
  auth: token,
  request: {
    fetch: fetch,
  },
});

dispatchWorkflow();

async function dispatchWorkflow() {
  try {
    const reposToDispatchComposerUpdate = await listBedrockRepos(owner);

    console.log(`Triggered by ${trigger}!`);
    for (repo of reposToDispatchComposerUpdate) {
      console.log(`Calling createWorkflowDispatch on ${repo.name}`);
      for (branchValue of bedrockBranchTarget[branch]) {
        console.log(`in branch ${branchValue}`);
        if (
          await checkBranchExists(repo.name, branchValue) &&
          await checkComposerPackage(trigger, 'pressbooks', repo.name, 'composer.json', branchValue)
        ) {
          console.log('dispatched')
          actionOctokit.rest.actions.createWorkflowDispatch({
              owner: owner,
              repo: repo.name,
              workflow_id: 'autoupdate.yml',
              ref: branchValue,
              inputs: {
                package: trigger,
              }
          }).then((response) => {
              console.log(`Github API response: ${response}`);
          });
        }
      }
    }
  } catch (error) {
    core.setFailed(error.message);
  }
}

async function listBedrockRepos(organization) {
  try {
    let allRepos = [];
    let page = 1;
    let reposResponse = [];

    do {
      reposResponse = await restOctokit.rest.repos.listForOrg({
        org: organization,
        type: 'private',
        per_page: 100,
        page,
      });
      allRepos.push(...reposResponse.data);
      page++;
    } while (reposResponse.data.length === 100);

    const bedrockRepos = allRepos.filter(repo => repo.name.endsWith('-bedrock'));

    console.log('Repositories ending with -bedrock:', bedrockRepos.map(repo => repo.name));
    return bedrockRepos;
  } catch (error) {
    console.error('Error fetching repositories:', error);
  }
}

async function checkComposerPackage(packageName, owner, repo, path, branch) {
  try {
    const { data } = await restOctokit.repos.getContent({
      owner,
      repo,
      path,
      ref: branch,
    });

    const content = Buffer.from(data.content, 'base64').toString();
    const composerJson = JSON.parse(content);
    const packageFound = composerJson.require && composerJson.require[packageName] !== undefined;

    console.log(`Package ${packageName} found: ${packageFound}`);
    return packageFound;
  } catch (error) {
    console.error('Error fetching composer.json:', error.message);
  }
}

async function checkBranchExists(repo, branch) {
  try {
    const { data } = await restOctokit.rest.repos.getBranch({
      owner,
      repo,
      branch,
    });

    console.log(`Branch ${branch} exists in ${owner}/${repo}`);
    return true;
  } catch (error) {
    if (error.status === 404) {
      console.log(`Branch ${branch} does not exist in ${owner}/${repo}`);
    } else {
      console.error(`Error checking branch existence: ${error.message}`);
    }
    return false;
  }
}
