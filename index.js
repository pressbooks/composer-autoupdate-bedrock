const core = require('@actions/core');
const { Octokit } = require("@octokit/action");

const reposToDispatchComposerUpdate = [
    'uses-updater',
    'pressbooksedu-golden-bedrock',
    'client-bedrock',
    'pressbookspublic-bedrock',
    'pressbookspub-bedrock',
    'wisc-bedrock',
];

try {
    const trigger = core.getInput('triggered-by');
    const token = core.getInput('token');
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
            ref: 'development',
        }).then((response) => {
            console.log(`Github API response: ${response}`);
        });
    }
} catch (error) {
    core.setFailed(error.message);
}
