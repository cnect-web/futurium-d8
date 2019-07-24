Start by creating an application using aws cli.
'''
aws --region eu-west-2 \
elasticbeanstalk create-application \
--application-name FuturiumD8
'''

While this finishes, create the database/database cluster using RDS.
'''
@todo: add command here.
'''

Add a new environment using the following command (adapt as necessary):
'''
aws --region eu-west-2 \
elasticbeanstalk create-environment \
--application-name FuturiumD8 \
--environment-name FuturiumD8-Tests \
--solution-stack-name "64bit Amazon Linux 2018.03 v2.8.12 running PHP 7.1" \
--option-settings file://options.txt
'''

You'll require an options file if you are creating the environment in a region that is not the default one for your profile.
'''
options.txt
[
    {
        "Namespace": "aws:autoscaling:launchconfiguration",
        "OptionName": "IamInstanceProfile",
        "Value": "aws-elasticbeanstalk-ec2-role"
    }
]
'''

IMPORTANT:
After this step, you need to update the database security group to allow connections to mysql from your instances.

Allow connections to the database security group you defined while creating the RDS instance coming not from the load balancer, but from the instances themselves.


After this, create the following environment variables by going to: Environment >> Configuration >> Software
'''
DATABASE_HOST
DATABASE_NAME
DATABASE_PASSWORD
DATABASE_USERNAME
ENVIRONMENT 		("production", "staging", "development")
'''

This will kill the old instance and spawn a new one.
You should wait a bit after this step, to prevent problems with leader selection.

Alternatively, you can upload the code twice.
This is only relevant during the initial install.

