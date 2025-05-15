<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Configuration;

final class SamlConfiguration implements SamlConfigurationInterface
{
    public function __construct(private SystemConfiguration $configuration)
    {
    }

    public function isActivated(): bool
    {
        return $this->configuration->isSamlActive();
    }

    public function getTitle(): string
    {
        return $this->configuration->getSamlTitle();
    }

    public function getProvider(): string
    {
        return $this->configuration->getSamlProvider();
    }

    public function getAttributeMapping(): array
    {
        return $this->configuration->getSamlAttributeMapping();
    }

    public function getRolesAttribute(): ?string
    {
        return $this->configuration->getSamlRolesAttribute();
    }

    public function getRolesMapping(): array
    {
        return $this->configuration->getSamlRolesMapping();
    }

    public function isRolesResetOnLogin(): bool
    {
        return $this->configuration->isSamlRolesResetOnLogin();
    }

    public function getConnection(): array
    {
        $config = $this->configuration->getSamlConnection();
        
        // Get base configuration from environment
        $baseUrl = getenv('SAML_CONNECTION_BASE_URL') ?: 'https://timesheet.shorthills.ai';
        $entityId = getenv('SAML_IDP_ENTITY_ID') ?: 'https://sts.windows.net/85a77b6c-a790-4bcf-8fd6-c1f891dd360b/';
        $ssoUrl = getenv('SAML_IDP_SSO_URL') ?: 'https://login.microsoftonline.com/85a77b6c-a790-4bcf-8fd6-c1f891dd360b/saml2';
        $sloUrl = getenv('SAML_IDP_SLO_URL') ?: 'https://login.microsoftonline.com/85a77b6c-a790-4bcf-8fd6-c1f891dd360b/saml2';
        
        // Get and clean the certificate
        $x509cert = getenv('SAML_IDP_X509CERT');
        if (empty($x509cert)) {
            // If cert is empty in env, try to read from file
            $certPath = __DIR__ . '/../../TimeTrack.cer';
            if (file_exists($certPath)) {
                $x509cert = file_get_contents($certPath);
                error_log("Reading certificate from file: " . $certPath);
            } else {
                error_log("Certificate file not found at: " . $certPath);
            }
        }

        // Clean the certificate if it exists
        if (!empty($x509cert)) {
            // Keep the BEGIN/END tags as they are required
            $x509cert = trim($x509cert);
            error_log("Certificate content length: " . strlen($x509cert));
        } else {
            error_log("No certificate content found in environment or file");
        }

        $samlConfig = [
            'strict' => true,
            'debug' => true,
            'baseurl' => $baseUrl,
            
            // Service Provider Data
            'sp' => [
                'entityId' => rtrim($baseUrl, '/') . '/auth/saml/metadata',
                'assertionConsumerService' => [
                    'url' => rtrim($baseUrl, '/') . '/auth/saml/acs',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                ],
                'singleLogoutService' => [
                    'url' => rtrim($baseUrl, '/') . '/auth/saml/logout',
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => '',
                'privateKey' => '',
            ],
            
            // Identity Provider Data
            'idp' => [
                'entityId' => $entityId,
                'singleSignOnService' => [
                    'url' => $ssoUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => [
                    'url' => $sloUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => $x509cert,
            ],
            
            'security' => [
                'nameIdEncrypted' => false,
                'authnRequestsSigned' => false,
                'logoutRequestSigned' => false,
                'logoutResponseSigned' => false,
                'signMetadata' => false,
                'wantMessagesSigned' => false,
                'wantAssertionsSigned' => false,
                'wantNameIdEncrypted' => false,
                'requestedAuthnContext' => false,
                'wantXMLValidation' => true,
                'relaxDestinationValidation' => true,
                'destinationStrictlyMatches' => false,
                'allowRepeatAttributeName' => true,
                'rejectUnsolicitedResponsesWithInResponseTo' => false,
            ],
        ];

        error_log("SAML Configuration: " . json_encode($samlConfig));
        return $samlConfig;
    }
}
