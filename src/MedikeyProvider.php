<?php

namespace RedSnapper\Medikey;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RedSnapper\Medikey\Exceptions\InvalidSessionTicketException;
use RedSnapper\Medikey\Exceptions\InvalidTicketException;
use RedSnapper\Medikey\Exceptions\MissingTicketInResponseException;
use RedSnapper\Medikey\Exceptions\TicketMismatchException;
use RedSnapper\Medikey\Exceptions\MedikeyException;
use RedSnapper\Medikey\Exceptions\TicketNotFoundInSessionException;
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

    /**
     * @throws MedikeyException
     * @throws RequestException
     * @throws MissingTicketInResponseException
     * @throws InvalidTicketException
     */
    public function redirect(): RedirectResponse
    {
        $ticket = $this->getTicket();

        $query = http_build_query(['id' => $this->site_id, 't' => $ticket]);

        $this->request->session()->put('state', $ticket);

        return new RedirectResponse(self::BASE_URL."/login_process.aspx?".$query);
    }

    /**
     * @throws MedikeyException
     * @throws RequestException
     * @throws TicketNotFoundInSessionException
     * @throws InvalidSessionTicketException
     */
    public function user(): MedikeyUser
    {
        if (isset($this->user)) {
            return $this->user;
        }

        $this->validateTicketFromMedikeyMatchesSessionValue();

        $this->user = $this->getUserByTicket($this->request->get('t'));

        return $this->user;
    }

    /**
     * @throws RequestException
     * @throws MissingTicketInResponseException
     * @throws MedikeyException
     * @throws InvalidTicketException
     */
    protected function getTicket(): string
    {
        $response = Http::get(self::BASE_URL."/ticket.aspx", [
            'id' => $this->site_id,
        ]);

        $response->throw();

        $xml = $this->toXml($response);
        if (!isset($xml->ticket_numero)) {
            throw new MissingTicketInResponseException();
        }

        $ticket = (string)$xml->ticket_numero;
        if (!$this->ticketIsValid($ticket)) {
            throw new InvalidTicketException($ticket, $this->site_id);
        }

        return $ticket;
    }

    /**
     * @throws MedikeyException
     * @throws RequestException
     */
    private function getUserByTicket($ticket): MedikeyUser
    {
        $response = Http::get(self::BASE_URL."/profilo.aspx", [
            'id' => $this->site_id,
            't'  => $ticket,
        ]);

        $response->throw();

        $data = json_decode(json_encode($this->toXml($response)), true);

        return new MedikeyUser($data);
    }

    /**
     * @throws MedikeyException
     */
    private function toXml(Response $response): SimpleXMLElement
    {
        $xml = simplexml_load_string($response->body(), \SimpleXMLElement::class, LIBXML_NOCDATA);

        $this->validateResponse($xml);

        return $xml;
    }

    private function validateResponse(SimpleXMLElement $element): void
    {
        if ($element->errore_id != 0) {
            throw new MedikeyException($element->errore_descrizione, (int)$element->errore_id);
        }
    }

    /**
     * @throws TicketNotFoundInSessionException
     * @throws InvalidSessionTicketException
     * @throws TicketMismatchException
     */
    private function validateTicketFromMedikeyMatchesSessionValue(): void
    {
        $ticket = $this->request->session()->pull('state');
        if (is_null($ticket)) {
            throw new TicketNotFoundInSessionException();
        }

        if (!$this->ticketIsValid($ticket)) {
            throw new InvalidSessionTicketException($ticket);
        }

        if ($this->request->input('t') !== $ticket) {
            throw new TicketMismatchException($ticket, $this->request->input('t'));
        }
    }

    private function ticketIsValid(string $ticket): bool
    {
        return strlen($ticket) > 0 && $ticket != 0;
    }
}