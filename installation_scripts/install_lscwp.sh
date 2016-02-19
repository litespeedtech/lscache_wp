


if [ "$1" == '--help' ]
then
    printf "\n*************************************\n"
	printf "\nPossible Commands:\n\n\n"
	printf "To enable/disable LSCWP:\n"
	printf "===============================\n" 
	printf "./install_lscwp.sh [enable/disable] LSPHP_PATH WP_DIR1_PATH WP_DIR2_PATH ...\n" 
	printf "\nor\n\n" 
	printf "./install_lscwp.sh [enable/disable] LSPHP_PATH -f < wpInstalls.txt_file\n\n\n" 
	printf "To find all WP installs in a certain directory and create a wpInstalls.txt file:\n" 
    printf "===============================\n" 
	printf "./install_lscwp.sh find DIR_PATH\n\n\n" 
	printf "To check a directory for known cache plugins and their statuses:\n"
	printf "===============================\n"
	printf "./install_lscwp.sh status LSPHP_PATH WP_DIR_PATH\n\n\n"
	printf "*************************************\n\n"

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
                        	sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php enable ${WP_DIR}
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
				sudo -u ${USER} ${LSPHP_PATH} ${WP_DIR}/lscwp_enable_disable.php enable ${WP_DIR}
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
