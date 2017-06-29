# backmon - Monitoring and purging of scheduled backups
This PHP script executes monitoring and purging operations by a defined JSON file.

The idea is that each backup script has its own JSON `backup\_definition.json* file which specifies how and when the backups are generated.

## Installation

- Get Composer to install the required dependencies.
- To use size policies with files larger than 2 GByte, you need to install php-curl.

## Usage
`backmon` supports different command line arguments

	# search for backup_definitions.json in given directories
	php runner.php [arguments] <path/to/directoryA> <path/to/directoryB> <path/to/directory...>
	
	# use custom JSON file(s)
	php runner.php [arguments] <path/to/configA.json> <path/to/configB.json> <path/to/config....json>

	# file contains all JSON paths and base directories
	php runner.php [arguments] -c path/to/defintions_container.json
	
	
### -a | --action
With `-a | --action` you can specify the action to execute. This can be 'omd', 'info' or 'cleanup (NOT YET)

### -c | --config-definitions
To scan for different backup_definitions.json you can either use the last command line parameters for this or you define multiple file containing these backup locations.
If you use the `-c` switch you have to point the value to a JSON file which has the format

	{
		"/path/to/backup_definitions.json": null /* or "path/to/base/directory"
	}

### -p | --policy
You can enable only specific policies by using the `-p` switch:

	php runner.php -p cron,retention

would only execute the *cron* and *retention* policies but no other.

### -f | --force
Force purging. Otherwise only a dry-run is executed. No file is removed then.

### --before-definition
Execute script before the definition runs. As first argument the base directory is passed to the script.

### --after-definition
Execute script after the definition has been run. As first argument the base directory is passed to the script.

## Configuration
In the root directory of your backup target create a `backup\_definition.json` with the following content

	[{
		"name": "Name of your backup script",
		"cron": "a valid cron expression when this backup script runs",
		"directories": {
			"my-backup-dir": {
				"files": {
					"backup.tar.gz": {
						"name": "My backup file",
					}
				}
			}
		}
	}]

By running `php runner.php %path\_to\_backup\_definition.json` backmon builds a backup definition based upon the JSON file.

### Default configurations
With help of the *defaults* section you can define attributes which are used in each of the *files* entries. Each *file* entry can override the configuration attribute.

	[{
		"name": "Name of your backup script",
		"cron": "a valid cron expression when this backup script runs",
		"defaults": {
			"purge": "true"
		},
		"directories": {
			"my-dir": {
				"files": {
					"my-file.tar.gz": {
						"comment": "setting 'purge' is not required as it is already defined in 'defaults' section"
					}
				}
			}
		}
	}]

### Cron expression
The attribute `cron` must contain a valid crontab entry without comments.

### Defining directories
Each element of the `directories` specifies a directory which contains backup files of any sort. For a generic configuration you can use the placeholder `{{ ... }}`. Every placeholder is assigned to a variable in the underlying context.

	"directories": {
		"mysql/{{year}}/{{month}}": {
		}
	}

	matches the folder mysql/2017/01, mysql/2017/02 and so on. The variables ${year} and ${month} contain the name of the subfolders.

### Defining files
Files contain the real backup file like TARed SQL dumps etc.:

	"files": {
		"sqldump.tar.gz": {
			"name": "SQL dump"
		}
	}

You can also use variable like

	"directories": {
		"mysql/{{year}}/{{month}}": {
			"files": {
				"sqldump_${year}_${month}.tar.gz": { }
			}
		}
	}

This would match the file mysql/2017/01/sqldump_2017_01.tar.gz but (obviously) not mysql/2017/01/sqldump_2016_01.tar.gz.

You can also use the following shortcodes which resolve into the given variables

- %Y: year (yyyy)
- %y: year_two_digits (yy)
- %m: month (mm)
- %d: day (dd)
- %H: hour (HH)
- %M: minute (MM)
- %S: second (SS)
- %i: any integer
- %w: a single word
- %W: a wildcard (matches anything)

#### How can I use shortcodes on changing filenames?
Imagine that you automatically export PDF files from your Atlassian Confluence installation. theses filenames are in the format `SPACE-123-123-123.pdf`. The first characters is the name of the Confluence space. The three dash-separated parts with digits are random. If you want to ensure that the file has been generated, you create a matcher like

                        "files": {
                                "SPACE-%W.pdf": {
				}
			}

			# or
                        
			"files": {
                                "SPACE-%i-%i-%i.pdf": {
				}
			}

To group the files together, you use the *grouping* option to reference the matcher:

			"files": {
                                "SPACE-%W.pdf": {
					"grouping": "${matcher}"
				}
			}

Your OMD check will be outputted like this

	SPACE-%W.pdf_last_backup - OK - Last backup run ...
	
#### Overwriting the OMD Item name
As you can see in the last paragraph, the OMD item name is constructed by default of the following parameters:
	
	${_.name}/${_.action.omd.group_key}${_.action.omd.suffix}_${_.action.omd.check_type}

- *_.name* is the name of the backup definition
- *_.action.omd.group_key* maps to the option "grouping" and is the *path* parameter by default
- *_.action.omd.suffix* maps to the option "suffix" and is empty by default
- *_.action.omd.check_type* maps to last_backup, size, retention etc.

If you want your OMD check item not be named like

	SPACE-%.pdf_last_backup

but
	my_space_backup_last_backup

you can use the property *check_key_format*:

	"files": {
		"SPACE-%W.pdf": {
			"check_key_format": "my_space_backup_${_.action.omd.check_type}"
		}
	}


#### Operations on variables
You can use variable operations like `lower` and `upper` to change the variables content. For example you have the following structure:

	backups/
	backups/CUSTOMER1
	backups/CUSTOMER1/customer1_2017.tar.gz
	backups/CUSTOMER2/customer2_2016.tar.gz
	backups/CUSTOMER2/customer2_2015.tar.gz

To match the backup of each customer you can use

	"directories": {
		"{{customer}}": {
			"files": {
				"${customer:lower}_%Y.tar.gz": {
					"sort": "filename",
					"grouping": "${customer}"
				}
			}
		}
	}

This would result in different groups:

	- CUSTOMER1: customer1_2017.tar.gz
	- CUSTOMER2: customer2_2015.tar.gz, customer2_2016.tar.gz
	
If you want to switch the order of the filename, use the "order" attribute:

				"${customer:lower}_%Y.tar.gz": {
					"sort": "filename",
					"order": "desc",
					"grouping": "${customer}"
				}

By default, the order is "asc".

### Grouping
By default backmon assumes that in each directory there are all required backup files like backup/1.tar.gz, backup/2.tar.gz and so on.
From a logical point of view it could be that backups are organized in another way so that for each backup run a new backup directory is created like backup/2017-01-16/backup.tar.gz, backup/2017-01-17.tar.gz and so on.
With help of the `grouping` attribute in the `files` definition you can group backup files together over different folders. The `grouping` attribute allows you to use variables.

For the backup strategy with yyyy-mm-dd/backup.tar.gz we can use the following definition:

	"directories": {
		"{{year}}-{{month}}": {
			"files": {
				"backup.tar.gz": {
					"grouping": "${filename}"
				}
			}	
		}
	}

### Sorting
After the grouping of files have finished, all collected files for the group in this file definition are sorted. By default they are sorted by the `filename` context attribute, which makes sense if you are using a file structure like
- 2017-01-16-backup.tar.gz
- 2017-01-17-backup.tar.gz
- 2017-01-18-backup.tar.gz

If you have group files in different directories but with the same backup file name like "backup.tar.gz" this won't work. By using the `sort` attribute you can sort the collected files of group by an context attribute, e.g.

	"directories": {
		"{{year}}-{{month}}": {
			"files": {
				"backup.tar.gz": {
					"grouping": "${filename}",
					"sort": "ctime"
				}
			}
		}
	} 

Please note that the newest file is always the __last__ element. If you want to change the order you can use *"order": "desc"*

### Suffix
When a directory contains multiple backup definitions you want OMD to recognize both definitions.
When the directory "my-directory" contains the two subdirectories "A" and "B":

	"directories": {
		"my-directory/{{dir}}": {
			"files": {
				"attachments-${j.%Y}-${j.%d}": {
					"suffix": "_attachments			
				},
				"database-${j.%Y}-${j.%d}": {
					"suffix": "_database"
				}
			}
		}
	}

When running backmon, the OMD output definition is

	/home/backup/my-directory/A_attachments_attachments_retention;
	/home/backup/my-directory/database_attachments_retention;


### Context attributes

- _.root: base directory of backups, containing the `backup\_definition.json` file
- filename: filename without path
- size: size in bytes
- mtime: mtime
- ctime: ctime
- atime: atime
- path: full path to the directory containing the current file

### Policies
Policies are applied after all grouping and sorting has been done. The policies itself are returning only status values in an OMD/check_mk friendly way but could be used for other purposes as well.

#### Retention
A retention defines how many files of a grouped fileset should be kept. The attributes `retention` and `retention\_min\_fail`, `retention\_max` and `retention\_max\_fail` are specifiny the boundaries.
Please note that the retention uses the __number__ of existing files and __not__ the days from the last backup. This ensures that you have at last the ${retention} number of backups, even if the backup failed in between.

#### Size
The size retention can be used to determine a specific minimum or maximum file size of a backup, to ensure that the backup has been run. Attributes are `size`, `size\_min\_fail`, `size\_max` and `size\_max\_fail`.

Please note that you need to install `php-curl` because of PHP internals files can not be larger than 2 GByte. We make use BigFileTools (https://github.com/jkuchar/BigFileTools) which fixes this problem.

#### Cronjob
The cronjob policy is implicitly executed by getting the last (=newest) backup of the sorted group and checking its ctime value against the last expected Cronjob run (global `cron` attribute).
If the backup is before (=older) than the last Cronjob run, an error is raised.

## Actions
### info
Shows informational messages about the JSON definition.

### omd
Prints out the result as local OMD checks. Please note that you have to re-inventorize your OMD host after you have changed/added/removed a backup.

### purge
Purges each file which is outside the retention policy. This ensures that you have at least the ${retention} number of backups.
The purge action does only explicitly deletes the backup files and no parent directory, if it is empty after purging.
For cleaning up empty directories I suggest you use this bash one-liner discussed at http://unix.stackexchange.com/questions/24134/remove-empty-directory-trees-removing-as-many-directories-as-possible-but-no-fi

	find /path/to/backup-parent -type d -depth -empty -exec rmdir "{}" \;

This command should be executed after the purge cron job has been run.
Please note that by default the purging is only simulated (dry-run). You have to pass the parameter *-f|--force* when running:

	php runner.php -a purge --force

#### Execute a purge script after each definition has been run
To purge all empty directories you can also use Backmon. Save the following script as e.g. /tmp/purge_empty_directories.sh

	#!/bin/bash

	if [ ! $1 ]; then
			echo "usage $0 path/to/parent"
			exit
	fi

	if [ ! -d $1 ]; then
			echo "Path '$1' is not a directory"
	fi

	if [ "$1" == "/" ]; then
			echo "Path '/' is protected"
	fi

	# remove any empty directory in the specified directory but not the top directory
	find $1 -depth -type d -not -path $1 -empty -exec rmdir "{}" \;


Execute Backmon's purge action with
	
	php runner.php -a purge --force --after-definition "/tmp/purge_empty_directories.sh"
	
## Processing files
- Iterate over each `backup\_definition.json` file
	- Read `backup\_definition.json` file
		- Iterate over each element of `directories` attribute
		- If the directory definition contains a variable assignment `{{ ... }}` iterate over each subdirectory
			- Iterate over each file definition of the `files` attribute
				- Collect all matching files in this directory
	- Group all collected files of each `files` definition by using the `grouping` attribute
	- Sort each group of files by using the `sort` attribute
	- Execute the queried action like "OMD" or "Purge"
