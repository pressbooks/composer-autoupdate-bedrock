const core = require('@actions/core');
const { Octokit } = require("@octokit/action");

// TODO: set JSON secret in repo settings
const reposToCallAction = [
    'uses-updater',
];

try {
    const trigger = core.getInput('triggered-by');
    const token = core.getInput('token');
    const octokit = new Octokit({
        auth: token,
    });
    console.log(`Triggered by ${trigger}!`);
    for (let repo in reposToCallAction) {
        console.log(`Calling createWorkflowDispatch on ${repo}`);
        octokit.rest.actions.createWorkflowDispatch({
            owner: 'pressbooks',
            repo: repo,
            workflow_id: 'autoupdate.yml',
            ref: 'main',
        }).then((response) => {
            console.log(`Github API response: ${response}`);
        });
    }
} catch (error) {
    core.setFailed(error.message);
}
