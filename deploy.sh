#!/usr/bin/bash

if [ "$1" == "--install" ]; then
	echo "Installing module...";
	mkdir -p /usr/local/cwpsrv/var/services/user_files/modules/sa_filters

	cp sa_filters.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters.php
	cp sa_filters.ini /usr/local/cwpsrv/var/services/users/cwp_lang/en/sa_filters.ini
	cp mod_sa_filters.html /usr/local/cwpsrv/var/services/users/cwp_theme/original/mod_sa_filters.html
	cp sa_filters.js.twig /usr/local/cwpsrv/var/services/users/cwp_theme/original/js/modules/sa_filters.js.twig
	cp Log.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/Log.php
	cp UserPrefs.php /usr/local/cwpsrv/var/services/user_files/modules/sa_filters/UserPrefs.php

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

	echo "Plugin removed.";
fi
