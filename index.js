const core = require('@actions/core');
const {Octokit} = require("@octokit/action");

const reposToDispatchComposerUpdate = [
    'uses-updater'
];

try {
    const trigger = core.getInput('triggered-by');
    const token = core.getInput('token');
    let branch = core.getInput('branch');
    branch === 'refs/heads/production' ? branch = 'staging' : branch = 'dev';
    const octokit = new Octokit({
        auth: token,
    });
    console.log(`Triggered by ${trigger}!`);
    for (const repo of reposToDispatchComposerUpdate) {
        console.log(`Calling createWorkflowDispatch on ${repo}`);
        octokit.rest.actions.createWorkflowDispatch({
            owner: 'pressbooks',
            repo: repo,
            workflow_id: 'autoupdate.yml',
            ref: branch,
        }).then((response) => {
            console.log(`Github API response: ${response}`);
        });
    }
} catch (error) {
    core.setFailed(error.message);
}
