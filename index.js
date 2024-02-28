const fetch = require('node-fetch');
const core = require('@actions/core');
const {
  Octokit: ActionOctokit
} = require("@octokit/action");
const {
  Octokit: RestOctokit
} = require("@octokit/rest");
const isGitHubActions = process.env.GITHUB_ACTION;
// const isGitHubActions = false;
const owner = 'pressbooks';
const bedrockBranchTarget = {
  'dev': ['dev', 'lti-development'],
  'staging': ['staging'],
  'production': ['production']
};


trigger();

async function trigger() {
  try {
    // Check if running in GitHub Actions environment
    // const isGitHubActions = process.env.GITHUB_ACTION || false;

    // Use core.getInput if in GitHub Actions, otherwise use a default value or environment variable
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

    const reposToDispatchComposerUpdate = await listBedrockRepos(owner);

    console.log(`Triggered by ${trigger}!`);
    for (repo of reposToDispatchComposerUpdate) {
      if (repo.name === 'testing-bedrock') {
        console.log(`Calling createWorkflowDispatch on ${repo.name}`);
        for (branchValue of bedrockBranchTarget[branch]) {
          console.log(`in branch ${branchValue}`);
          if (
            await checkBranchExists(repo.name, branchValue, token) &&
            await checkComposerPackage(trigger, 'pressbooks', repo.name, 'composer.json', branchValue, token)
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

    const token = isGitHubActions ? core.getInput('token') : process.env.INPUT_TOKEN || 'default-token';

    const restOctokit = new RestOctokit({
      auth: token,
      request: {
        fetch: fetch,
      },
    });

    // Fetch all repositories for the organization
    do {
      reposResponse = await restOctokit.rest.repos.listForOrg({
        org: organization,
        type: 'private', // Adjust this as needed
        per_page: 100,
        page,
      });
      allRepos.push(...reposResponse.data);
      page++;
    } while (reposResponse.data.length === 100); // GitHub API pagination max is 100 items

    // Filter repositories by name pattern *-bedrock
    const bedrockRepos = allRepos.filter(repo => repo.name.endsWith('-bedrock'));

    console.log('Repositories ending with -bedrock:', bedrockRepos.map(repo => repo.name));
    return bedrockRepos;
  } catch (error) {
    console.error('Error fetching repositories:', error);
  }
}

async function checkComposerPackage(packageName, owner, repo, path, branch, token) {
  try {

    const restOctokit = new RestOctokit({
      auth: token,
      request: {
        fetch: fetch,
      },
    });

    // Fetch the content of composer.json from the repository
    const {
      data
    } = await restOctokit.repos.getContent({
      owner,
      repo,
      path,
      ref: branch,
    });

    // GitHub API returns the content in base64 encoding
    const content = Buffer.from(data.content, 'base64').toString();

    // Parse the JSON content
    const composerJson = JSON.parse(content);

    // Check if the package exists in the 'require' section
    const packageFound = composerJson.require && composerJson.require[packageName] !== undefined;

    console.log(`Package ${packageName} found: ${packageFound}`);
    return packageFound;
  } catch (error) {
    console.error('Error fetching composer.json:', error.message);
  }
}

async function checkBranchExists(repo, branch, token) {
  try {
    const restOctokit = new RestOctokit({
      auth: token,
      request: {
        fetch: fetch,
      },
    });
    // Attempt to get the branch
    const {
      data
    } = await restOctokit.rest.repos.getBranch({
      owner,
      repo,
      branch,
    });

    // If the request is successful, the branch exists
    console.log(`Branch ${branch} exists in ${owner}/${repo}`);
    return true;
  } catch (error) {
    // If the branch is not found, GitHub API throws a 404 error
    if (error.status === 404) {
      console.log(`Branch ${branch} does not exist in ${owner}/${repo}`);
    } else {
      // Log other errors
      console.error(`Error checking branch existence: ${error.message}`);
    }
    return false;
  }
}
