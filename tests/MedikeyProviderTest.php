<?php

namespace RedSnapper\Medikey\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RedSnapper\Medikey\Exceptions\InvalidSessionTicketException;
use RedSnapper\Medikey\Exceptions\InvalidTicketException;
use RedSnapper\Medikey\Exceptions\MissingTicketInResponseException;
use RedSnapper\Medikey\Exceptions\TicketMismatchException;
use RedSnapper\Medikey\Exceptions\MedikeyException;
use RedSnapper\Medikey\Exceptions\TicketNotFoundInSessionException;
use RedSnapper\Medikey\MedikeyProvider;
use Spatie\ArrayToXml\ArrayToXml;

class MedikeyProviderTest extends TestCase
{
    /** @test */
    public function can_be_redirected_to_provider()
    {
        Http::fake([
            '/ticket*' => Http::response(
                ArrayToXml::convert(['ticket_numero' => 456], 'ticket')
            ),
        ]);

        $provider = new MedikeyProvider($this->setRequestWithSession(), '123');

        $this->assertEquals(
            MedikeyProvider::BASE_URL."/login_process.aspx?id=123&t=456",
            $provider->redirect()->getTargetUrl()
        );

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === MedikeyProvider::BASE_URL."/ticket.aspx?id=123";
        });
    }

    /** @test */
    public function can_fetch_user()
    {
        Http::fake([
            '/profilo*' => Http::response(
                ArrayToXml::convert(['utente_id' => 23, 'nome' => 'John', 'cognome' => 'Doe'], 'profilo')
            ),
        ]);

        $provider = new MedikeyProvider($this->setRequestWithSession(['t' => 456], ['state' => 456]), '123');

        $user = $provider->user();

        $this->assertEquals(23, $user->getId());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('John Doe', $user->getName());

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() === MedikeyProvider::BASE_URL."/profilo.aspx?id=123&t=456";
        });
    }

    /** @test */
    public function fetching_user_throws_an_exception_when_there_is_an_error()
    {
        Http::fake([
            '/profilo*' => Http::response(
                ArrayToXml::convert(['errore_id' => 99, 'errore_descrizione' => 'My error'], 'profilo')
            ),
        ]);
        $provider = new MedikeyProvider($this->setRequestWithSession(['t' => 456], ['state' => 456]), '123');

        $this->expectExceptionMessage("My error");
        $this->expectException(MedikeyException::class);

        $provider->user();
    }

    /** @test */
    public function fetching_user_throws_an_exception_when_there_is_a_ticket_mismatch()
    {
        Http::fake([
            '/profilo*' => Http::response(
                ArrayToXml::convert(['errore_id' => 99, 'errore_descrizione' => 'My error'], 'profilo')
            ),
        ]);
        $provider = new MedikeyProvider($this->setRequestWithSession(['t' => 456], ['state' => 'doesnotmatch']), '123');

        $this->expectException(TicketMismatchException::class);

        $provider->user();
    }


    /** @test */
    public function redirect_throws_an_exception_when_get_ticket_response_returns_an_error_code()
    {
        Http::fake([
            '/ticket*' => Http::response(
                ArrayToXml::convert(['errore_id' => 99, 'errore_descrizione' => 'My error'], 'profilo')
            ),
        ]);

        $provider = new MedikeyProvider($this->setRequestWithSession(), 5);

        $this->expectException(MedikeyException::class);
        $this->expectExceptionMessage("My error");

        $provider->redirect();
    }

    /** @test */
    public function redirect_throws_an_exception_when_ticket_number_key_is_not_present_in_response()
    {
        Http::fake([
            '/ticket*' => Http::response(
                ArrayToXml::convert(['foo' => 'bar'], 'ticket')
            ),
        ]);

        $this->expectException(MissingTicketInResponseException::class);

        $provider = new MedikeyProvider($this->setRequestWithSession(), 5);
        $provider->redirect();
    }

    public static function invalidTicketNumbers(): array
    {
        return [
            [0],
            [''],
        ];
    }

    /**
     * @test
     * @dataProvider invalidTicketNumbers
     */
    public function redirect_throws_an_exception_when_medikey_fails_to_provide_a_valid_ticket_number(mixed $ticketNum)
    {
        Http::fake([
            '/ticket*' => Http::response(
                ArrayToXml::convert(['ticket_numero' => $ticketNum], 'ticket')
            ),
        ]);

        $this->expectException(InvalidTicketException::class);

        $provider = new MedikeyProvider($this->setRequestWithSession(), 5);
        $provider->redirect();
    }

    /** @test */
    public function fetching_user_throws_an_exception_when_ticket_number_is_not_in_session()
    {
        $provider = new MedikeyProvider($this->setRequestWithSession(['t' => 456]), '123');

        $this->expectException(TicketNotFoundInSessionException::class);

        $provider->user();
    }

    /**
     * @test
     * @dataProvider invalidTicketNumbers
     */
    public function fetching_user_throws_an_exception_when_session_ticket_number_is_invalid(mixed $ticketNum)
    {
        $provider = new MedikeyProvider($this->setRequestWithSession(['t' => 456], ['state' => $ticketNum]), '123');

        $this->expectException(InvalidSessionTicketException::class);

        $provider->user();
    }

    protected function setRequestWithSession(array $requestData = [], $sessionData = []): Request
    {
        $request = new Request($requestData);
        $session = $this->app->make('session')->driver('array');
        $session->put($sessionData);
        $request->setLaravelSession($session);

        return $request;
    }
}