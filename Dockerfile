FROM docker.pkg.github.com/linkorb/php-docker-base/php-docker-base:latest

RUN mkdir -p -m 0600 ~/.ssh && ssh-keyscan github.com >> ~/.ssh/known_hosts

# linkorb
ARG DEPLOY_KEY_LINKORB
RUN mkdir -p -m 0777 /usr/src/linkorb-schemata && mkdir /app/public/linkorb/
RUN ssh-agent sh -c 'echo "$DEPLOY_KEY_LINKORB" | ssh-add -; git clone git@github.com:linkorb/linkorb-schema /usr/src/linkorb-schemata'
RUN cd /usr/src/linkorb-schemata && /usr/bin/composer install
RUN /usr/src/linkorb-schemata/vendor/bin/schemata generate:html-doc /usr/src/linkorb-schemata/schema /app/public/linkorb/

# plaza
ARG DEPLOY_KEY_PLAZA
RUN mkdir -p -m 0777 /usr/src/plaza && mkdir /app/public/plaza
RUN ssh-agent sh -c 'echo "$DEPLOY_KEY_PLAZA" | ssh-add -; git clone git@github.com:linkorb/plaza /usr/src/plaza'
RUN /usr/src/linkorb-schemata/vendor/bin/schemata generate:html-doc /usr/src/plaza/schema /app/public/plaza/

# userbase
#ARG DEPLOY_KEY_USERBASE
#RUN mkdir -p -m 0777 /usr/src/userbase && mkdir /app/public/userbase
#RUN ssh-agent sh -c 'echo "$DEPLOY_KEY_USERBASE" | ssh-add -; git clone git@github.com:linkorb/userbase /usr/src/userbase'
#RUN /usr/src/linkorb-schemata/vendor/bin/schemata generate:html-doc /usr/src/userbase/schema /app/public/userbase/

# hub
#ARG DEPLOY_KEY_HUB
#RUN mkdir -p -m 0777 /usr/src/hub && mkdir /app/public/hub
#RUN ssh-agent sh -c 'echo "$DEPLOY_KEY_HUB" | ssh-add -; git clone git@github.com:linkorb/hub /usr/src/hub'
#RUN /usr/src/linkorb-schemata/vendor/bin/schemata generate:html-doc /usr/src/hub/schema /app/public/hub/

# herald

RUN cd /usr/src && rm -rf * && cd /app/public/ && rm -rf build

ENTRYPOINT ["apache2-foreground"]
