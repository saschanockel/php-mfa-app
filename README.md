# How to use

Just start the `docker-compose` setup with `docker-compose up -d`. After that set the correct permissions for the `public/img` folder with `sudo chown -R 82:82 public/img` so the `www-data` user in the container can save QR codes. Finally do a `composer install` and visit the app at `http://localhost:8080`.