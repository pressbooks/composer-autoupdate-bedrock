const AWS = require('aws-sdk');

const trigger = process.env.INPUT_TRIGGERED_BY || 'default-trigger';
let branch = process.env.BRANCH || 'refs/heads/dev';
branch === 'refs/heads/production' ? branch = 'staging' : branch = 'dev';
const topicArn =  (branch === 'dev') ? process.env.AWS_SNS_ARN_DEV : process.env.AWS_SNS_ARN_STAGING;
const message = {"Message": trigger};

const params = {
  Message: JSON.stringify(message),
  TopicArn: topicArn,
  MessageGroupId: 'github-repo-update',
};

AWS.config.update({
  accessKeyId: process.env.AWS_ACCESS_KEY_ID,
  secretAccessKey: process.env.AWS_SECRET_ACCESS_KEY,
  region: 'ca-central-1',
});

const sns = new AWS.SNS({apiVersion: '2010-03-31'});

console.log("params: ", params);
sns.publish(params, function(err, data) {
  if (err) {
    console.error("Error sending message: ", err);
  } else {
    console.log("Message sent successfully: ", data.MessageId);
  }
});