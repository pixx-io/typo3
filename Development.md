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