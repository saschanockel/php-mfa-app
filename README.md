# How to use

Just start the `docker-compose` setup with `docker-compose up -d`. After that set the correct ownership for
the `public/img` folder if not UID and GID 1000, so the the container can save QR code images.
Finally do a `composer install` and visit the app at `http://localhost:8080`.
