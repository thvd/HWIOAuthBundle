<?php

/*
 * This file is part of the HWIOAuthBundle package.
 *
 * (c) Hardware.Info <opensource@hardware.info>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HWI\Bundle\OAuthBundle\OAuth\Response;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * SensioUserResponse
 *
 * @author Joseph Bielawski <stloyd@gmail.com>
 * @author SensioLabs <contact@sensiolabs.com>
 */
class SensioConnectUserResponse extends AbstractUserResponse implements AdvancedUserResponseInterface
{
    /**
     * @var \DOMXpath
     */
    protected $xpath;

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->getNodeValue('./foaf:name', $this->response);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayName()
    {
        return $this->getNodeValue('./foaf:name', $this->response);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->getNodeValue('./foaf:mbox', $this->response);
    }

    /**
     * {@inheritdoc}
     */
    public function getProfilePicture()
    {
        return $this->getNodeValue('./atom:link[@rel="foaf:depiction"]', $this->response, 'link');
    }

    /**
     * {@inheritdoc}
     */
    public function setResponse($response)
    {
        $dom = new \DOMDocument();
        try {
            if (!$dom->loadXML($response)) {
                throw new \ErrorException('Could not transform this xml to a \DOMDocument instance.');
            };
        } catch(\Exception $e) {
            throw new AuthenticationException('Could not retrieve valid user info.');
        }
        $this->xpath = new \DOMXpath($dom);

        $nodes = $this->xpath->evaluate('/api/root');
        $user  = $this->xpath->query('./foaf:Person', $nodes->item(0));
        if (1 !== $user->length) {
            throw new AuthenticationException('Could not retrieve user info.');
        }

        $this->response = $user->item(0);
    }

    /**
     * @param string      $query
     * @param \DOMElement $element
     * @param string      $nodeType
     *
     * @return mixed|null
     */
    protected function getNodeValue($query, \DOMElement $element, $nodeType = 'normal')
    {
        $nodeList = $this->xpath->query($query, $element);
        if ($nodeList && $nodeList->length > 0) {
            $node = $nodeList->item(0);
            switch ($nodeType) {
                case 'link':
                    $nodeValue = $node->attributes->getNamedItem('href')->value;
                    break;

                case 'normal':
                default:
                    $nodeValue = $node->nodeValue;
                    break;
            }

            return $this->sanitizeValue($nodeValue);
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function sanitizeValue($value)
    {
        if ('true' === $value) {
            $value = true;
        } elseif ('false' === $value) {
            $value = false;
        } elseif (empty($value)) {
            $value = null;
        }

        return $value;
    }
}