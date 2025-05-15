<?php

// Read the certificate from the .env file
$certFromEnv = getenv('SAML_IDP_X509CERT');
echo "Cert length from env: " . strlen($certFromEnv) . PHP_EOL;

// Clean the certificate (remove quotes, newlines, etc.)
$cleanCert = trim(str_replace(['"', "'", "\r", "\n", "%"], '', $certFromEnv));
echo "Cert length after cleaning: " . strlen($cleanCert) . PHP_EOL;
echo "First 30 chars: " . substr($cleanCert, 0, 30) . PHP_EOL;

// Format the certificate for output
$formattedCert = wordwrap($cleanCert, 64, "\n", true);
echo "\nFormatted certificate to copy:\n";
echo "SAML_IDP_X509CERT=" . $cleanCert . PHP_EOL;

// Try to load the certificate
if (openssl_x509_read("-----BEGIN CERTIFICATE-----\n" . $formattedCert . "\n-----END CERTIFICATE-----")) {
    echo "\nCertificate is valid and can be loaded by OpenSSL\n";
} else {
    echo "\nERROR: Certificate is not valid or cannot be loaded by OpenSSL\n";
    echo openssl_error_string() . PHP_EOL;
}

// Double check with a different method
$pemCert = "-----BEGIN CERTIFICATE-----\n" . $formattedCert . "\n-----END CERTIFICATE-----";
$certInfo = openssl_x509_parse($pemCert);
if ($certInfo) {
    echo "Certificate parsed successfully. Subject: " . $certInfo['subject']['CN'] . PHP_EOL;
} else {
    echo "Failed to parse certificate\n";
} 