from php:cli-alpine

run docker-php-ext-install mysqli
run wget https://getcomposer.org/installer && \
    php installer --install-dir=bin --filename=composer && \
    rm installer