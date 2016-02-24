
printHelp() {
    printf "\n*************************************\n" 
	printf "\nLSCWP Install Script Help Page\n" 
	printf "\nUsage: ./install_lscwp.sh [command] [parameters]\n\n" 
	printf "\nPossible Commands:\n\n" 
	printf "  find [DIR_PATH]\n" 
	printf "\tSearch a directory for all WordPress installations within its subdirectories. This command will create a wpInstalls.txt file.\n\n" | fold -sw 80 
	printf "  enable/disable [LSPHP_PATH] [-f WP_INSTALLS_FILE | WP_PATH1 WP_PATH2...]\n" 
	printf "\tEnable or disable LSWCP for specified WordPress installations. The list of WordPress installs may be passed in with the command or if the -f parameter is set, a file is expected to be redirected in.\n\n" | fold -sw 80
	printf "  status [LSPHP_PATH] [WP_PATH]\n" 
	printf "\tCheck the status of all known cache plugins for a WordPress installation. This command will output a list of cache plugins and their status (enabled/disabled). This may not list all cache plugins.\n\n\n" | fold -sw 80
	printf "Example Usage:\n\n"  
	printf "Find all installations:\n"  
	printf "./install_lscwp.sh find /path/to/all/installs\n\n"  
	printf "Enable LSCWP on all installations:\n"  
	printf "./install_lscwp.sh enable -f < wpInstalls.txt\n\n"  
	printf "Disable LSCWP on a specific install:\n"  
	printf "./install_lscwp.sh disable /path/to/specific/install\n\n"  
	printf "Check the status of a specific install:\n"  
	printf "./install.lscwp.sh status /path/to/specific/install\n\n"  
	printf "*************************************\n\n"  
}
function htaccess_create (){
    sudo -u ${1} touch "${2}/.htaccess2"
    
    printf "# BEGIN WordPress\n" > "${2}/.htaccess2"
    printf "# END WordPress\n\n" >> "${2}/.htaccess2"
    printf "<IfModule Litespeed>\n" >> "${2}/.htaccess2"
    printf "CacheLookup public on\n" >> "${2}/.htaccess2"
    printf "</IfModule>\n" >> "${2}/.htaccess2"
}

function htaccess_modify (){
    
    grep -q "<IfModule \+Litespeed *>" "${WP_DIR}/.htaccess2"
    if [ $? -eq 0 ]
    then
        grep -q ' *CacheLookup \+public \+.\+' "${WP_DIR}/.htaccess2"
        if [ $? -eq 0 ]
        then
            sed -i 's/ *CacheLookup \+public \+.\+/CacheLookup public on/' ${WP_DIR}/.htaccess2
        else
            sed -i '/<IfModule \+Litespeed *>/a CacheLookup public on' ${WP_DIR}/.htaccess2
        fi
    else
        sed -i '/# END WordPress/a \\n<IfModule Litespeed>\nCacheLookup public on\n</IfModule>\n' ${WP_DIR}/.htaccess2
    fi
}

if [ "$1" == '--help' ]
then
    printHelp
elif [ "$1" == "find" -a "$#" -eq 2 ]
then
	if [ "${2}" != "/" ]
	then
		SEARCH_DIR="${2%/}"
	else
		SEARCH_DIR="${2}"
	fi
	
	if [ -d "${SEARCH_DIR}" ]
	then
		if [ ! -f "wpInstalls.txt" ]
		then
			for CHILD_DIR in $(find "${SEARCH_DIR}" -name wp-content -print)
			do
				parent=$(dirname $CHILD_DIR)
				echo "${parent}" >> wpInstalls.txt
			done
		else
			COUNT=1
			while [ -f "wpInstalls${COUNT}.txt" ]
			do
				(( COUNT++ ))
			done
			
			for CHILD_DIR in $(find "${SEARCH_DIR}" -name wp-content -print)
            do
                parent=$(dirname $CHILD_DIR)
                echo "${parent}" >> "wpInstalls${COUNT}.txt"
            done
		fi
	fi

elif [ "${1}" == "status" -a "$#" -eq 3 ]
then
	LSPHP_PATH=${2}

	if [ "${3}" != "/" ]
	then
		WP_DIR="${3%/}" 
	else
		WP_DIR="${3}"
	fi
	
	if [ -d "${WP_DIR}" ]
	then
		USER=`ls -ld ${WP_DIR} | awk '{print $3}'`

        sudo -u ${USER} cp "lscwp_enable_disable.php" "${WP_DIR}"
        sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php status ${WP_DIR}
        rm "${WP_DIR}/lscwp_enable_disable.php"

		exit 0
	fi

elif [ "${1}" == "enable" -a "${3}" == "-f" ]
then
    LSPHP_PATH=${2}

    while IFS= read -r DIR || [[ -n "$DIR" ]];
    do	
		if [ "${DIR}" != "/" ]
		then
            WP_DIR="${DIR%/}"
		else
			WP_DIR="${DIR}"
		fi

        if [ -d "${WP_DIR}" ]
        then
			if [ ! -d "${WP_DIR}/wp-content/plugins/woocommerce" ]
			then
                USER=`ls -ld ${WP_DIR} | awk '{print $3}'`

                sudo -u ${USER} cp "lscwp_enable_disable.php" "${WP_DIR}"
                sudo -u ${USER} cp -r "litespeed-cache" "${WP_DIR}/wp-content/plugins/"
                if [ ! $(sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php enable ${WP_DIR}) ]
                then
                    if [ -e "${WP_DIR}/.htaccess2" ]
                    then
                        if [ -w "${WP_DIR}/.htaccess2" ]
                        then
                            htaccess_modify ${WP_DIR}
                            unset htaccess_modify
                        else
                            echo "${WP_DIR} - Unable to write to .htaccess file."
                        fi
                    else
                        htaccess_create ${USER} ${WP_DIR}
                        unset htaccess_create
                    fi
                fi       	
                rm "${WP_DIR}/lscwp_enable_disable.php"
			else
				echo -e "\n${WP_DIR} - WooCommerce installation detected. LSCWP not installed.\n"
				exit 1
			fi
        fi
    done


elif [ "${1}" == "enable" -a "$#" -gt 2 ]
then
	LSPHP_PATH=${2}
	shift 2

	while [ "$#" -gt 0 ]
	do
		if [ "${1}" != "/" ]
		then
			WP_DIR="${1%/}"
		else
			WP_DIR="${1}"
		fi

		if [ -d "${WP_DIR}" ]
		then
			if [ ! -d "${WP_DIR}/wp-content/plugins/woocommerce" ]
			then
				USER=`ls -ld ${WP_DIR} | awk '{print $3}'`

				sudo -u ${USER} cp "lscwp_enable_disable.php" "${WP_DIR}"
				sudo -u ${USER} cp -r "litespeed-cache" "${WP_DIR}/wp-content/plugins/"
				if [ ! $(sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php enable ${WP_DIR}) ]
                then
                    if [ -e "${WP_DIR}/.htaccess2" ]
                    then
                        if [ -w "${WP_DIR}/.htaccess2" ]
                        then
                           htaccess_modify ${WP_DIR}
                           unset htaccess_modify
                        else
                            echo "${WP_DIR} - Unable to write to .htaccess file."
                        fi
                    else
                        htaccess_create ${USER} ${WP_DIR}
                        unset htaccess_create
                    fi
                fi
				
                rm "${WP_DIR}/lscwp_enable_disable.php"
			else
				echo -e "\n${WP_DIR} - WooCommerce installation detected. LSCWP not installed.\n"
                exit 1
			fi
		fi
		
		shift
	done
	
elif [ "${1}" == "disable" -a "${3}" == "-f" ]
then
    LSPHP_PATH=${2}

    while IFS= read -r DIR || [[ -n "$DIR" ]];
    do
        if [ "${DIR}" != "/" ]
        then
            WP_DIR="${DIR%/}"
        else 
            WP_DIR="${DIR}"
        fi

        if [ -d "${WP_DIR}" ]
        then
            if [ -d "${WP_DIR}/wp-content/plugins/litespeed-cache" ]
            then
                USER=`ls -ld ${WP_DIR} | awk '{print $3}'`

                sudo -u ${USER} cp "lscwp_enable_disable.php" "${WP_DIR}"
                sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php disable ${WP_DIR}
                rm "${WP_DIR}/lscwp_enable_disable.php"
                rm -r "${WP_DIR}/wp-content/plugins/litespeed-cache/"
            fi
        fi
    done

elif [ "${1}" == "disable" -a "$#" -gt 2 ]
then
	LSPHP_PATH=${2}
	shift 2

	while [ "$#" -gt 0 ]
	do
		if [ "${1}" != "/" ]
		then
			WP_DIR="${1%/}"
		else
			WP_DIR="${1}"
		fi

		if [ -d "${WP_DIR}" ]
		then
			if [ -d "${WP_DIR}/wp-content/plugins/litespeed-cache" ]
			then
				USER=`ls -ld ${WP_DIR} | awk '{print $3}'`
					
				sudo -u ${USER} cp "lscwp_enable_disable.php" "${WP_DIR}"
				sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php disable ${WP_DIR}
                rm "${WP_DIR}/lscwp_enable_disable.php"
				rm -r "${WP_DIR}/wp-content/plugins/litespeed-cache/"
			fi
		fi

		shift
	done

else
	printf "Invalid Input! Try --help for a list of commands.\n\n"
	exit 1
fi
