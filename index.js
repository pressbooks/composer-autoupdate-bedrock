const AWS = require('aws-sdk');
const core = require('@actions/core');

const isGitHubActions = process.env.GITHUB_ACTION || false;
const trigger = isGitHubActions ? core.getInput('triggered-by') : process.env.INPUT_TRIGGERED_BY || 'default-trigger';
let branch = isGitHubActions ? core.getInput('branch') : process.env.INPUT_BRANCH || 'refs/heads/dev';
branch === 'refs/heads/production' ? branch = 'staging' : branch = 'dev';

let topicArn =  (branch === 'dev') ? process.env.AWS_SQS_ARN_DEV : process.env.AWS_SQS_ARN_PROD;

AWS.config.update({
  accessKeyId: process.env.AWS_ACCESS_KEY_ID,
  secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY,
  region: 'ca-central-1',
});

const sns = new AWS.SNS({apiVersion: '2010-03-31'});

const message = {"Message": trigger};

const params = {
  Message: JSON.stringify(message),
  TopicArn: topicArn,
  MessageGroupId: 'github-repo-update',
};

console.log("params: ", params);
sns.publish(params, function(err, data) {
  if (err) {
    console.error("Error sending message: ", err);
  } else {
    console.log("Message sent successfully: ", data.MessageId);
  }
});