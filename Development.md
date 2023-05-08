# Development

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
6. TBD: Create a symlink in `public/typo3conf/ext` to `../../../local_packages/pixxio_extension`

### Update the plugin from Github repository

1. Pull the code from the repository with `git pull`
2. Reinstall the plugin `composer require pixxio/pixxio-extension`

## Publish new release

- Create a new branch
- Update the version in `ext_emconf.php` and `composer.json`
- Run `composer install`
- Push the branch and merge it into the master
- Checkout master
- Create and push a new tag: `git tag 1.0.4 && git push --tags`
- Login to https://packagist.org/ and click the "Update" button at https://packagist.org/packages/pixxio/pixxio-extension
- Wait until the change is visible in Typo3 TER (can take some hours)