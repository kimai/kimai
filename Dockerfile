# Offizielles Kimai Docker-Image verwenden
FROM kimai/kimai2:apache

# Setze das Arbeitsverzeichnis
WORKDIR /opt/kimai

# Kopiere alle Dateien in das Container-Dateisystem
COPY . .

# Setze die Dateiberechtigungen
RUN chown -R www-data:www-data /opt/kimai/var /opt/kimai/public/avatars /opt/kimai/public/thumbnail /opt/kimai/public/var

# Exponiere Port 8001
EXPOSE 8001

# Starte den Apache-Server
CMD ["apache2-foreground"]
