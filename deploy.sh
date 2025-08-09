#!/usr/bin/bash

RED="\e[31m"
GREEN="\e[32m"
YELLOW="\e[33m"
CLR="\e[0m"

function install_module_files {
	cp sa_filters.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php
	cp sa_filters.ini /usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini
	cp mod_sa_filters.html /usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html
	cp sa_filters.js.twig /usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig
	cp Log.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php
	cp UserPrefs.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php
	cp menu_sa_filters.html /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_sa_filters.html
	cp config.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/config.php

	echo '{% include "menu_sa_filters.html" %}' >> /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_left.html
}

function uninstall_module_files {
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php
	rm -f /usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_sa_filters.html
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/config.php

	sed -i 's/{% include "menu_sa_filters.html" %}//' /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_left.html
}

function is_module_installed {
	files=(
		"/usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php",
		"/usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini",
		"/usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html",
		"/usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig",
		"/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php",
		"/usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php",
		"/usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_sa_filters.html"
	)

	missing_file_count=0
	for file in ${!files[@]}; do
		if ! [ -f "$file" ]; then
			missing_file_count=$missing_file_count+1
		fi
	done

	if [ $missing_file_count -gt 0 ]; then
		if [ $missing_file_count -eq "${#files[@]}" ]; then
			return false
		fi
		return false
	else
		return true
	fi
}

function has_amavis_database {
	config_enabled=$(grep "^@lookup_sql_dsn" /etc/amavisd/amavisd.conf | wc -c)
	default_db_exists=$(mysql -Nse "show databases" | grep "amavis" | wc -c)

	if [ $default_db_exists -gt 0 ]; then
		return "amavis";
	fi

	if [ $config_enabled -gt 0 ]; then
		database_name=$(sed -ne '/@lookup_sql_dsn/,/\(.*)\);/p' amavisd.conf | grep -oE "database=([a-zA-Z0-9\-\_\.]+)" | awk -F'=' '{print $1}')
		config_db_exists=$(mysql -Nse "show databases" | grep ${database_name} | wc -c)

		if [ $config_db_exists -gt 0 ]; then
			return ${database_name}
		fi
	else
		return "amavis"
	fi
	return false;
}

function get_amavis_db_host {
	db_host=$(sed -ne '/@lookup_sql_dsn/,/\(.*)\);/p' amavisd.conf | grep -oE "host=([a-zA-Z0-9\-\_\.]+)" | awk -F'=' '{print $2}')
	return $db_host
}

function get_amavis_db_user {
	db_user=$(sed -ne '/@lookup_sql_dsn/,/\(.*)\);/p' amavisd.conf | awk -F',' '{print $2}' | tr -d '\n' | tr -d "'")
	return $db_user
}

function get_amavis_db_pass {
	db_pass=$(sed -ne '/@lookup_sql_dsn/,/\(.*)\);/p' amavisd.conf | sed -ne '/@lookup_sql_dsn/,/\(.*)\);/p' | awk -F',' '{print $3}' | tr -d '\n' | tr -d "'" | sed 's/\].)\;$//')
	return $db_pass
}

function has_standard_amavis_tables {
	if [ "$@" -eq 0 ]; then
		echo "Error: Must pass a database name to has_standard_amavis_tables"
		return false;
	fi

	tables=$(mysql -Nse "show tables" $1)

	if [ $(contains_item $tables "users ") ] && [ $(contains_item $tables "wblist") ] && [ $(contains_item $tables "mailaddr") ]; then
		return true
	else
		echo -e "${YELLOW}Warning${CLR}: Amavis database exists but is missing one or more of the standard tables"
		return false
	fi
}

function contains_item {
	if [ "$@" -eq 0 ]; then
		echo -e "${RED}Error${CLR}: contains_item expects two items, an array and a string"
		return false;
	fi

	if [[ " ${1[*]} " =~ " ${2} " ]]; then
		return true
	fi
	return false
}

function install_standard_amavis_tables {
	if [ -f "standard_amavis_tables.sql" ]; then
		db_name=$(has_amavis_database)

		if [ "$db_name" != "0" ]; then
			mysql $db_name < standard_amavis_tables.sql
		fi
	fi
}

function install_user_map_table {
	if [ -f "user_maps_amavis_table.sql" ]; then
		db_name=$(has_amavis_database)

		if [ "$db_name" != "0" ]; then
			mysql $db_name < user_maps_amavis_table.sql
		fi
	fi
}

function create_amavis_database {
	db_name='amavis'
	db_user='amavis'
	db_pass=$(head -c 12 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9!@#$%^&*()-_=+')

	if [ "$#" -gt 1 ]; then
		db_name=$1
		db_user=$2
	elif [ "$#" -eq 1 ]; then
		db_name=$1
	fi

	mysql -e "CREATE DATABASE '${db_name}'"
	mysql -e "CREATE USER '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}'"
	mysql -e "GRANT ALL PRIVILEGES ON ${db_name}.* TO '${db_user}'@'localhost'"
	mysql -e "FLUSH PRIVILEGES"

	touch config.php
	# cat << EOF > config.php
	# <?php
	# \$config['amavis']['host'] = 'localhost';
	# \$config['amavis']['name'] = '${db_name}';
	# \$config['amavis']['user'] = '${db_user}';
	# \$config['amavis']['pass'] = '${db_pass}';
	# ?>
	# EOF
}

function create_amavis_config {
	db_name=$(has_amavis_database)
	db_user=$(get_amavis_db_user)
	db_pass=$(get_amavis_db_pass)
	db_host=$(get_amavis_db_host)

	touch config.php
	# cat << EOF > config.php
	# <?php
	# \$config['amavis']['host'] = '${db_host}';
	# \$config['amavis']['name'] = '${db_name}';
	# \$config['amavis']['user'] = '${db_user}';
	# \$config['amavis']['pass'] = '${db_pass}';
	# ?>
	# EOF
}

if [ "$#" -eq 0 ] || [ "$1" == "--update" ]; then
	echo "Downloading current module..."
	echo
	install_dir="/tmp/sa_filter_module"
	mkdir -p $install_dir

	if [ $(command -v git) ]; then
		git clone --quiet https://github.com/cborrow/sa_filters.git $install_dir > /dev/null
		cd ${install_dir}
	else
		echo -e "${YELLOW}Notice{$CLR}: git not found, downloading current zip instead..."
		wget https://github.com/cborrow/sa_filters/archive/refs/heads/main.zip -O ${install_dir}/sa_filter.zip
		unzip /tmp/sa_filter_module/sa_filter.zip -d $install_dir
		cd ${install_dir}/sa_filters-main
	fi

	echo "Installing module...";
	echo
	mkdir -p /usr/local/cwpsrv/var/services/user_files/modules/sa_filters

	install_module_files

	if [ ! $(has_amavis_database) ]; then
		create_amavis_database
	else
		 create_amavis_config
	fi

	if [ ! $(has_standard_amavis_tables) ]; then
		install_standard_amavis_tables
		install_user_map_table
	else
		install_user_map_table
	fi

	cd /tmp
	rm -rf /tmp/sa_filter_module

	read -p "${GREEN}Module installed{$CLR}. Do you wish to restart CWP services now? (y/n): " restart_cwp

	if [ "$restart_cwp" == "y" ] || [ "$restart_cwp" == "Y" ]; then
		systemctl restart cwpsrv cwpsrv-phpfpm cwp-phpfpm
	fi

elif [ "$#" -gt 0 ] && [ "$1" == "--install" ]; then
	echo "Checking files..."
	echo

	files=("sa_filters.php", "sa_filters.ini", "mod_sa_filters.html", "sa_filters.js.twig", "Log.php", "UserPrefs.php", "menu_sa_filters.html")

	for file in $[!files[@]]; do
		if ! [ -f $file ]; then
			echo -e "${RED}Error${CLR}: One or more module files are missing from the current directory."
			exit 1
		fi
	done

	install_module_files;


fi

if [ "$#" -gt 0 ] && [ "$1" == "--uninstall" ]; then
	echo "Removing module...";

	uninstall_module_files;
	
	echo "Plugin removed.";
fi
