echo "
 --------------- Installing the Cassini Team Webpages Framework ---------------"
printf "|
| CASH uses a set of xml files that define all content that goes into the
| framework. These xml files can potentially contain content that you do not
| want visible to all users. While there is nothing preventing you from placing
| these files in any directory of your choosing, it is HIGHLY RECOMMENDED that
| you place your xml folder outside of the public domain (i.e. not under /www).
|
| With that in mind, where would you like to install the xml files?
| (default is ${PWD}/xml)
|   --> "
    read xmlPath
    if [ ! -n "$xmlPath" ]; then
        xmlPath=${PWD}/xml
    fi
    shouldBlock=false
    case ${PWD} in
        $xmlPath*)
            shouldBlock=true
            message="Your xml directory cannot be a parent directory of ${PWD}"
            ;;
    esac
    if [ ${PWD} = $xmlPath ]; then
        shouldBlock=true
        message="You cannot place your xml files in the current directory."
    fi
    case $xmlPath in
        */)
            shouldBlock=true
            message="Your xml directory name cannot end with a slash" ;;
    esac
    while [ $shouldBlock = true ] #[ ${PWD} = $xmlPath ]
    do
        printf "|
| $message
| Where would you like to put them instead?
|   --> "
        read xmlPath
        if [ ! -n "$xmlPath" ]; then
            xmlPath=${PWD}/xml
        fi
        shouldBlock=false
        case ${PWD} in
            $xmlPath*)
                shouldBlock=true
                message="Your xml directory cannot be a parent directory of ${PWD}"
                ;;
        esac
        if [ ${PWD} = $xmlPath ]; then
            shouldBlock=true
            message="You cannot place your xml files in the current directory."
        fi
        case $xmlPath in
            */)
                shouldBlock=true
                message="Your xml directory name cannot end with a slash" ;;
        esac
    done
    printf "|
| Are you sure you want to install the xml files to '$xmlPath'?
|   [Y/n]: "
    read yn
    if [ "$yn" = "y" ] || [ "$yn" = "Y" ] || [ "$yn" = "" ]; then
        echo "|"
        echo "| Creating backups..."
        if [ -f start.html ]; then
            cp start.html start.html_backup
        fi
        if [ -f config.xml ]; then
            cp config.xml config.xml_backup
        fi
        if [ -f content.xml ]; then
            cp content.xml content.xml_backup
        fi
        if [ -f index.php ]; then
            cp index.php index.php_backup
        fi
        if [ -f map.xml ]; then
            cp map.xml map.xml_backup
        fi
        if [ -d ic ]; then
            cp -r ic ic_backup
        fi
        if [ -d resources ]; then
            cp -r resources resources_backup
        fi
        if [ "$xmlPath" = "" ]; then
            xmlPath=${PWD}/xml
        fi
        if [ -d $xmlPath ]; then
            cp -r $xmlPath "$xmlPath""_backup"
        else
            mkdir -p $xmlPath
        fi
        echo "|"
        echo "| Unarchiving..."
        tar -xf cash.bin
        echo "|"
        echo "| Moving xml files to $xmlPath..."
        mv content.xml $xmlPath
        if [ -f $xmlPath/content.xml ]; then
            echo "|   Moved content.xml"
        fi
        mv config.xml $xmlPath
        if [ -f $xmlPath/config.xml ]; then
            echo "|   Moved config.xml"
        fi
        cp resources/index.php $xmlPath #Make sure to insert an index.php in xml
        if [ -d $xmlPath/ic ]; then
            rm -r $xmlPath/ic
        fi
        mv ic $xmlPath/
        if [ -d $xmlPath/ic ]; then
            cp resources/scripts/index.php $xmlPath/ic
            echo "|   Moved ic"
        fi
        echo "|"
        echo "| Updating php scripts to point to specified xml location..."
        sed 's|<xml_directory>xml_directory_here</xml_directory>|<xml_directory>'${xmlPath}'</xml_directory>|g' map.xml > map.tmp
        mv map.tmp map.xml
        rm -rf cash.bin install.sh
        printf "|
| Do you want to keep backups of user-edited files prior to installing CASH?
|   [Y/n]: "
        read yn
        if [ "$yn" = "n" ] || [ "$yn" = "N" ]; then
            keepBackups=false
        else
            keepBackups=true
        fi
    else
        keepBackups=false
        echo "|
| Aborting installation..."
    fi
    if [ $keepBackups = false ]; then
        echo "|"
        echo "| Removing backups..."
        if [ -f start.html_backup ]; then
            rm start.html_backup
        fi
        if [ -f config.xml_backup ]; then
            rm config.xml_backup
        fi
        if [ -f content.xml_backup ]; then
            rm content.xml_backup
        fi
        if [ -f index.php_backup ]; then
            rm index.php_backup
        fi
        if [ -f map.xml_backup ]; then
            rm map.xml_backup
        fi
        if [ -d ic_backup ]; then
            rm -r ic_backup
        fi
        if [ -d resources_backup ]; then
            rm -r resources_backup
        fi
        if [ -d xml_backup ]; then
            rm -r xml_backup
        fi
        if [ -d "$xmlPath""_backup" ]; then
            rm -r "$xmlPath""_backup"
        fi
    else
        echo "|"
        echo "| Backups Kept"
    fi
    echo "|"
    echo "| Finishing Installation..."
echo "|
 ------------------------------------------------------------------------------"
echo
