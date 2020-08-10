<?php

declare(strict_types=1);

namespace InShore\BookWhen;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use InShore\BookWhen\Exception;
use InShore\BookWhen\Interfaces\ClientInterface;
use InShore\BookWhen\Validator;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Client
 *
 * The main class for API consumption
 *
 * @package inshore-packages\bookwhen
 * @todo comments
 * @todo externalise config
 * @todo fix token
 */
class Client implements ClientInterface
{
    
    /** @var string The API access token */
    private static $token = null;
    
    /** @var string The instance token, settable once per new instance */
    private $instanceToken;
    
    private $apiBaseUri;
    
    private $apiQuery;
   
    private $apiResource;
    
    private $apiVersion;
    
    private $validator;
    
    private $guzzleClient;
    
    /**
     * @param string|null $token The API access token, as obtained on diffbot.com/dev
     * @throws DiffbotException When no token is provided
     */
    public function __construct($token = null)
    {
        
        $this->apiBaseUri = 'https://api.bookwhen.com/';
            
        $this->apiQuery = [];
        
        $this->apiVersion = 'v2';
        
        $this->validator = new Validator();
        
        $this->guzzleClient = new GuzzleClient([
            'base_uri' => $this->apiBaseUri
        ]);
        
        if ($token === null) {
            if (self::$token === null) {
                $msg = 'No token provided, and none is globally set. ';
                $msg .= 'Use Diffbot::setToken, or instantiate the Diffbot class with a $token parameter.';
                throw new Exception($msg);
            }
        } else {
            if ($this->validator->validToken($token)) {
                self::$token = $token;
                $this->instanceToken = self::$token;
            }
        }
    }
    
    /**
     * @todo debug flag
     */
    protected function request(): ResponseInterface
    {
        try {
            // Authorization.
            $requestOptions = [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->instanceToken . ':')
                ]
            ];
            
            // Query.
            if (!empty($this->apiQuery) && is_array($this->apiQuery)) {
                $requestOptions['query'] = $this->apiQuery;
            }
   
            //$requestOptions['debug'] = true;
            
            return $this->guzzleClient->request('GET', $this->apiResource, $requestOptions);
           
        } catch (Exception $e) {
            // @todo;
            throw \Exception::class;
        }
    }
    
    /**
     * @todo
     */
    public function getAttachment($attachmentId)
    {
        if (!empty($attachmentId && !$this->validator->validId($attachmentId, 'attachment'))) {
            throw \Exception::class;
        }
        $this->apiResource = $this->apiVersion . '/attachments' . '/' . $attachmentId;
     
        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            $attachment = $body->data;
            $return = $attachment;
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getAttachments()
     */
    public function getAttachments(): array
    {    
        
        $this->apiResource = $this->apiVersion . '/attachments';
        
        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            
            foreach ($body->data as $attachment) {
                array_push($return, $attachment);
            }
            
            return $return;
        } catch (Exception $e) {
            throw \Exception::class;
            // @todo
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getClassPass()
     */
    public function getClassPass($classPassId)
    {
        $this->apiResource = $this->apiVersion . '/class_passes';
       
        if (!empty($classPassId && !$this->validator->validId($classPassId, 'classPass'))) {
            throw \Exception::class;
        }
     
        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            $classPass = $body->data;
            $return = $classPass;
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getClassPasses()
     */
    public function getClassPasses(): array
    {   
        $this->apiResource = $this->apiVersion . '/???';
        
        // @todo prepocess response onto nice model objects.
        $Response = $this->request();
        return json_decode($Response->getBody()->getContents());
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getEvent()
     */
    public function getEvent($eventId)
    {
        if (!empty($eventId && !$this->validator->validId($eventId, 'event'))) {
            throw \Exception::class;
        }
        $this->apiResource = $this->apiVersion . '/events' . '/' . $eventId;
     
        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            $event = $body->data;
            $event->soldOut = (bool) ($event->attributes->attendee_count >= $event->attributes->attendee_limit);
            $return = $event;
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getEvents()
     */
    public function getEvents(
        $calendar = false,
        $entry = false,
        $location = [],
        $tags = [],
        $title = [],
        $detail= [],
        $from = null,
        $to = null,
        $includeLocation = false,
        $includeAttachments = false,
        $includeTickets = false,
        $includeTicketsEvents = false,
        $includeTicketsClassPasses = false): array
    {    
        // Validate $tags.
        if (!empty($tags)) {
            if (!is_array($tags)) {
                // @todo throw \Exception::class;
            } else {
                $tags = array_unique($tags);
                foreach ($tags as $tag) {
                    if (!empty($tag) && !$this->validator->validTag($tag)) {
                        throw \Exception::class;
                    }
                }
            }
            $this->apiQuery['filter[tag]'] = implode(',', $tags);
        }
        
        // Validate $from;
        if (!empty($from)) {
            if (!$this->validator->validFrom($from, $to)) {
                throw \Exception::class;
            } else {
                $this->apiQuery['filter[from]'] = $from;
            }
        }
        
        // Validate $to;
        if (!empty($to)) {
            if (!$this->validator->validTo($to, $from)) {
                throw \Exception::class;
            } else {
                $this->apiQuery['filter[to]'] = $to;
            }
        }
        
        // API resource.
        $this->apiResource = $this->apiVersion . '/events';
        
        
        
        // Validate $includeLocation;
        
        // Validate $includeTickets;
  
        try {
            $Response = $this->request();
            
            $body = json_decode($Response->getBody()->getContents());
            
            // Prepocess response onto nice model objects.
            // @todo abstract.
            $return = [];
            
            foreach ($body->data as $event) {
                // Add additional properties here.
                $event->soldOut = (bool) ($event->attributes->attendee_count >= $event->attributes->attendee_limit);
                array_push($return, $event);
            }
            
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getLocation()
     */
    public function getLocation($locationId)
    {
        $this->apiResource = $this->apiVersion . '/locations';
        
        // if(!empty($eventId && !$this->Valdator->validId($ticketId))) {
        //     throw \Exception::class;
        // }
        
        try {
            $Response = $this->request();
            return json_decode($Response->getBody()->getContents());
        } catch (Exception $e) {
            // @todo
        }

    }
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getLocations()
     */
    public function getLocations(): array
    {
        $this->apiResource = $this->apiVersion . '/locations';

        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            
            foreach ($body->data as $location) {
                array_push($return, $location);
            }
            
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    } 
    
    /**
     *
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getTicket()
     */
    public function getTicket($ticketId)
    {        
        if (!empty($ticketId && !$this->Valdator->validId($ticketId, 'ticket'))) {
            throw \Exception::class;
        }

        $this->apiResource = $this->apiVersion . '/tickets';

        
        try {
            $Response = $this->request();
            return json_decode($Response->getBody()->getContents());
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \InShore\BookWhen\Interfaces\ClientInterface::getTickets()
     */
    public function getTickets($eventId): array
    {
        if (!$this->validator->validId($eventId, 'event')) {
            throw new \InvalidArgumentException('Invalid Event ID.');
        }

        $this->apiQuery = ['event' => $eventId];
        
        $this->apiResource = $this->apiVersion . '/tickets';
        
        $return = [];
        
        try {
            $Response = $this->request();
            $body = json_decode($Response->getBody()->getContents());
            
            foreach ($body->data as $ticket) {
                array_push($return, $ticket);
            }
            
            return $return;
        } catch (Exception $e) {
            // @todo
            throw \Exception::class;
        }
    }
    
    /**
     * Set Guzzle Client
     */
    public function setGuzzleClient($guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    } 
    
    /**
     * Sets the token for all future new instances
     * @param $token string The API access token, as obtained on diffbot.com/dev
     * @return void
     * @todo use the validator.
     */
    public static function setToken($token)
    {
        //self::validateToken($token);
        self::$token = $token;
    } 
}

// EOF!
