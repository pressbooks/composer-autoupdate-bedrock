const core = require('@actions/core');
// const {Octokit} = require("@octokit/action");
const { Octokit: ActionOctokit } = require("@octokit/action");
const { Octokit: RestOctokit } = require("@octokit/rest");
const isGitHubActions = process.env.GITHUB_ACTION;
// const bedrockBranchTarget = ['lti-development','dev','staging','production']
const bedrockBranchTarget = {};
bedrockBranchTarget['dev'] = ['dev','lti-development'];
bedrockBranchTarget['staging'] = ['staging'];
bedrockBranchTarget['production'] = ['production'];

trigger();

async function trigger() {
  try {
      // Check if running in GitHub Actions environment
      // const isGitHubActions = process.env.GITHUB_ACTION || false;

      // Use core.getInput if in GitHub Actions, otherwise use a default value or environment variable
      const trigger = isGitHubActions ? core.getInput('triggered-by') : process.env.INPUT_TRIGGERED_BY || 'default-trigger';
      const token = isGitHubActions ? core.getInput('token') : process.env.INPUT_TOKEN || 'default-token';
      const branch = isGitHubActions ? core.getInput('branch') : process.env.INPUT_BRANCH || 'refs/heads/dev';
      // branch === 'refs/heads/production' ? branch = 'staging' : branch = 'dev';

      const actionOctokit = new ActionOctokit({
          auth: token,
      });

      const reposToDispatchComposerUpdate = await listBedrockRepos("pressbooks");

      console.log(`Triggered by ${trigger}!`);
      for (const repo of reposToDispatchComposerUpdate) {
        for (const branchValue of bedrockBranchTarget[branch])
          console.log(`Calling createWorkflowDispatch on ${repo} in branch ${branchValue}`);
          // actionOctokit.rest.actions.createWorkflowDispatch({
          //     owner: 'pressbooks',
          //     repo: repo,
          //     workflow_id: 'autoupdate.yml',
          //     ref: branch,
          // }).then((response) => {
          //     console.log(`Github API response: ${response}`);
          // });
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
