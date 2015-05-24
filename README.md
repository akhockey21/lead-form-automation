# lead-form-automation
This repository will allow businesses to setup SMS, Calling(IVR), email, and billing to a database. You may use this script to sell leads to businesses, or use it for personal form automation and lead updates.


IMPORTANT NOTE: This script is currently being converted from procedural programming to Object oriented.

<strong>Status = In Progress, Working.</strong>


<h2>How To Use:</h2>

<strong>Config</strong>
The config file is self explanatory. Fill in the required settings that you would like. Including the required API keys.

<strong>Form</strong>
In main.php on line 37, you can add your own form inputs. It is required currently to keep 'location' as one. This is to route the lead or form submission to the correct store based on location. Any input is okay, as long as it is a locaiton. It will use google maps API to return the most relavant location.