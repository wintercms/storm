#
# Splits the core modules into their own repositories for separate inclusion through Composer
#

mkdir -p winter
pushd winter
./../git-subsplit.sh init git@github.com:wintercms/winter.git
./../git-subsplit.sh update
./../git-subsplit.sh publish --heads="1.0" --tags="v1.0.472" modules/backend:git@github.com:wintercms/wn-backend-module.git
./../git-subsplit.sh publish --heads="1.0" --tags="v1.0.472" modules/cms:git@github.com:wintercms/wn-cms-module.git
./../git-subsplit.sh publish --heads="1.0" --tags="v1.0.472" modules/system:git@github.com:wintercms/wn-system-module.git
popd
