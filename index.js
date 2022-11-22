import core from "@actions/core";
import github from "@actions/github";

try {
    const trigger = core.getInput('triggered-by');
    console.log(`Triggered by ${trigger}!`);
    const payload = JSON.stringify(github.context.payload, undefined, 2)
    console.log(`The event payload: ${payload}`);
} catch (error) {
    core.setFailed(error.message);
}
