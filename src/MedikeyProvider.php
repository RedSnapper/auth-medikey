<?php

namespace RedSnapper\Medikey;

use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RedSnapper\Medikey\Exceptions\InvalidTicketException;
use RedSnapper\Medikey\Exceptions\MedikeyException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MedikeyProvider
{
    private Request $request;

    private int $site_id;

    const BASE_URL = "https://ssl.medikey.it";

    private MedikeyUser $user;

    /**
     * MedikeyProvider constructor.
     */
    public function __construct(Request $request, int $site_id)
    {
        $this->request = $request;
        $this->site_id = $site_id;
    }

    public function redirect(): RedirectResponse
    {
        $ticket = $this->getTicket();

        $query = http_build_query(['id' => $this->site_id, 't' => $ticket]);

        $this->request->session()->put('state',$ticket);

        return new RedirectResponse(self::BASE_URL."/login_process.aspx?".$query);
    }

    public function user(): MedikeyUser
    {
        if (isset($this->user)) {
            return $this->user;
        }

        if ($this->hasInvalidTicket()) {
            throw new InvalidTicketException;
        }

        $this->user = $this->getUserByTicket($this->request->get('t'));

        return $this->user;
    }

    protected function getTicket(): string
    {
        $response = Http::get(self::BASE_URL."/ticket.aspx", [
          'id' => $this->site_id
        ]);

        $response->throw();

        $xml = simplexml_load_string($response->body(), \SimpleXMLElement::class, LIBXML_NOCDATA);

        return $xml->ticket_numero;
    }

    private function getUserByTicket($ticket): MedikeyUser
    {
        $response = Http::get(self::BASE_URL."/profilo.aspx", [
          'id' => $this->site_id,
          't' => $ticket
        ]);

        $response->throw();

        $data = json_decode(json_encode($this->toXml($response)), true);

        return new MedikeyUser($data);
    }

    private function toXml(Response $response): SimpleXMLElement
    {
        $xml = simplexml_load_string($response->body(), \SimpleXMLElement::class, LIBXML_NOCDATA);

        $this->validateResponse($xml);

        return $xml;
    }

    private function validateResponse(SimpleXMLElement $element)
    {
        if($element->errore_id != 0){

            throw new MedikeyException($element->errore_descrizione,(int) $element->errore_id);
        }
    }

    private function hasInvalidTicket():bool
    {
        $ticket = $this->request->session()->pull('state');

        return ! (strlen($ticket) > 0 && $this->request->input('t') === $ticket);
    }

}