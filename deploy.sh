#!/usr/bin/bash

if [ "$#" -eq 0 ] || [ "$1" == "--install" ]; then
	echo "Downloading current module..."
	echo
	install_dir="/tmp/sa_filter_module"
	mkdir -p $install_dir

	if [ $(command -v git) ]; then
		git clone --quiet https://github.com/cborrow/sa_filters.git $install_dir > /dev/null
		cd ${install_dir}
	else
		wget https://github.com/cborrow/sa_filters/archive/refs/heads/main.zip -O ${install_dir}/sa_filter.zip
		unzip /tmp/sa_filter_module/sa_filter.zip -d $install_dir
		cd ${install_dir}/sa_filters-main
	fi

	echo "Installing module...";
	echo
	mkdir -p /usr/local/cwpsrv/var/services/user_files/modules/sa_filters

	cp sa_filters.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php
	cp sa_filters.ini /usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini
	cp mod_sa_filters.html /usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html
	cp sa_filters.js.twig /usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig
	cp Log.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php
	cp UserPrefs.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php
	cp menu_sa_filters.html /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_sa_filters.html

	echo '{% include "menu_sa_filters.html" %}' >> /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_left.html

	cd /tmp
	rm -rf /tmp/sa_filter_module

	read -p "Module installed. Do you wish to restart CWP services now? (y/n): " restart_cwp

	if [ "$restart_cwp" == "y" ] || [ "$restart_cwp" == "Y" ]; then
		systemctl restart cwpsrv cwpsrv-phpfpm cwp-phpfpm
	fi
fi

if [ "$1" == "--uninstall" ]; then
	echo "Removing module...";

	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php
	rm -f /usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php
	rm -f /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php
	rm -f /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_sa_filters.html

	sed -i 's/{% include "menu_sa_filters.html" %}//' /usr/local/cwpsrv/var/services/users/cwp_theme/original/menu_left.html

	echo "Plugin removed.";
fi
