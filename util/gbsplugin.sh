rm ./moodle-ltiservice_gradebookservices/ltiservice_gradebookservices.zip
rm -rf ltiservice_gradebookservices
cp -r moodle-ltiservice_gradebookservices ltiservice_gradebookservices
rm -rf ltiservice_gradebookservices/.git
rm -rf ltiservice_gradebookservices/util
rm ltiservice_gradebookservices/.gitignore
rm -rf ltiservice_gradebookservices/scripts
zip -r moodle-ltiservice_gradebookservices/ltiservice_gradebookservices.zip ltiservice_gradebookservices


