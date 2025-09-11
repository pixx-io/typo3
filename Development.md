# Development

## Setup a local Typo3 system

You can use Docker and ddev to setup a local Typo3 instance.

Typo3 V11: https://docs.typo3.org/m/typo3/tutorial-getting-started/11.5/en-us/Installation/TutorialDdev.html
Typo3 V12: https://docs.typo3.org/m/typo3/tutorial-getting-started/12.4/en-us/Installation/TutorialDdev.html

## Important hints

Some functions are related to a external plugin called `filemetadata`. You should test the extension with and without the plugin.

https://packagist.org/packages/typo3/cms-filemetadata

## Development setup with composer

1. Create a folder `./local_packages`
2. Go inside the folder
3. Checkout the repo with `git clone https://github.com/pixx-io/typo3.git pixxio_extension`
4. Add to `composer.json` a local package repository:
   ```
   	"repositories": [
   		{
   		"type": "path",
   		"url": "./local_packages/*"
   		}
   	],
   ```
5. Install the plugin via `composer require pixxio/pixxio-extension`

### Testing Sync

#### Testing manual with ddev

You can run the sync command via the ddev cli:

`ddev typo3 pixxio:sync`

#### Testing with scheduler

You need one additional plugin:

`composer require typo3/cms-scheduler`

After the installation, there is a database error visible. To fix it, run:

`ddev typo3 extension:setup` (V11) or `ddev typo3 database:updateschema` (V12)

##### Add a scheduled task

- Login to the typo3 admin panel and select "Typo3 Scheduler" in the main menu.
- Add a new task and select:
  - Task: "Execute console command"
  - Schedulable Command. Save and reopen to define command arguments: "pixxio:sync"
  - Frequeny: "3600"

### Update the plugin from Github repository

1. Pull the code from the repository with `git pull`
2. Reinstall the plugin `composer require pixxio/pixxio-extension`

## Publish new release

- Create a new branch
- Update the version in `ext_emconf.php` and `composer.json`
- Update the version in `CHANGELOG.md`
- Run `composer install`
- Push the branch and merge it into the main
- Checkout main
- Create and push a new tag: `git tag 3.1.0 && git push --tags`
- Login to https://packagist.org/ and click the "Update" button at https://packagist.org/packages/pixxio/pixxio-extension
- Create a zip `zip -r ../pixxio_extension_3.1.0.zip *`
- Upload the extension to https://extensions.typo3.org/my-extensions
