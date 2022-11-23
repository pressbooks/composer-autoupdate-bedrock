const core = require('@actions/core');
const github = require('@actions/github');

try {
    const trigger = core.getInput('triggered-by');
    console.log(`Triggered by ${trigger}!`);
    const payload = JSON.stringify(github.context.payload, undefined, 2)
    console.log(`The event payload: ${payload}`);
} catch (error) {
    core.setFailed(error.message);
}
