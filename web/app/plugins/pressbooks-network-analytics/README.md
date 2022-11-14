# Pressbooks Network Analytics

Uses the `wp_blogmeta` meta  (introduced in WP 5.1) to store a collection of information about all books on a given network. 

Fancy charts. Filterable, searchable lists. 

## Setup

Requires a cron job that runs:

    cd /srv/www/pressbooks.test/current
    wp eval-file web/app/plugins/pressbooks-network-analytics/bin/sync/books.php

In dire straits, the process can be triggered manually by an administrator with hidden URL (not recommended):

+ https://pressbooks.test/wp/wp-admin/network/admin.php?page=pb_network_analytics_booklist_sync

## Airtable integration
Pressbooks uses [Airtable](https://airtable.com/) to keep track of business information about our clients. This plugin uses 
the Airtable API to update information about Pressbooks network managers in our company's Airtable database. Each time a 
Pressbooks network is created or updated the `NetworkManagersNotification` class is responsible for synchronizing the 
relevant information to Airtable through `add_site_option` and `update_site_option hooks`.

When a user is added as a Network Manager to a network:
1. The synchronization action looks for the relevant Network in the `Network` Airtable table and attempts to match the name 
in the Pressbooks DB with the name in Airtable.
2. If Network Name was found, we look try to match the user by matching the user's `email` with an email address for a person 
in our `Contacts` Airtable table. 
    - If no existing user is found, a new user is added in the `Contacts` Airtable's table using the PB user's 
    email and the Full Name (First Name + Last Name format). 
    If the PB user does not have a first and last name, we update their name in Airtable with their user `display_name`
3. We fill in the Network field in Airtable with the current Network's name, and check the 'Network Manager' field in Airtable.

When a user is removed as a Network Manager:
1. We attempt to find a `Contacts` record for the affected user, matching by email address.
2. When found, we remove the linked record for the relevant network from the contact's Networks field. If the users' networks field still contains one or more network records, we take no further action.
3. If the users' network field is empty at this point, we uncheck the 'Network Manager' checkbox field for the user in the `Contacts` base.

_Note: In order for synchronization to work correctly, the network name must be identical in the PB Database and the Airtable `Network` table.  If this condition is not met, synchronization will not be carried out._

### Airtable API
`AirtableClient` class is a wrapper responsible for the Airtable API connection which uses [Guzzle](https://docs.guzzlephp.org/en/stable/) 
for HTTP communication. Exceptions are logged with the [debug error log PB utility](https://github.com/pressbooks/pressbooks/blob/33859ec39b7f3da803e98ca1a378ac472220c1ef/inc/utility/namespace.php#L725).

### Environment Variables
See [Airtable API](https://airtable.com/api) for instructions on how to get your keys.
```
AIRTABLE_API_KEY=<YOUR AIRTABLE API KEY>
AIRTABLE_API_URL=<EX: https://api.airtable.com/v0/>
AIRTABLE_BASE_ID=<THE AIRTABLE BASE ID WITH CONTACTS AND NETWORKS TABLES>
```
For Staging and Development environments we use the [Airtable TEST API](https://airtable.com/tblqC7Lekj4Dkiuh2/) base.
