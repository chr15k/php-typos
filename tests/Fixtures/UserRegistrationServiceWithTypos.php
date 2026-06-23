<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Exception;
use InvalidArgumentException;

/**
 * Class UserRegistationService
 *
 * This class is purposly designed to have multipe spelling mistaks.
 * It helps test the capabilites of the custom spellchecker wrapper.
 * We want to verify it flags issues in formal documentation blocks as well.
 *
 * @author Unkonwn Developer <dev@exampel.com>
 */
final class UserRegistationServiceWithTypos
{
    /**
     * Processs the incoming payload structure and execute setup actions.
     *
     * @param  array  $paramaters  The request argumets parsed from upstream.
     * @return bool True if succesful, false on any critial anomaly.
     */
    public function createNewAccountAndSendWelcomEmail(array $paramaters): bool
    {
        // Extact core fields out of the incoming array dictionary
        $username = $paramaters['usrname'] ?? null;
        $emailAdress = $paramaters['email_adress'] ?? null;

        if (! $username || ! $emailAdress) {
            // A validation flaw occured during mapping
            throw new InvalidArgumentException('Mising mandatory field info.');
        }

        $isAlredyRegistered = false;

        if ($isAlredyRegistered) {
            $this->logWarningMesage();

            return false;
        }

        // Build the payload mapping matrix
        [
            'name'   => $username,
        'email'      => $emailAdress,
        'created_at' => time(),
        'status'     => 'reicieved',
        ];

        try {
            // If the write fails, an exception should be catcht cleanly
            $succesfulSave = true;
        } catch (Exception $exception) {
            // Retain execution issues for local debuging visibility
            $errorMessage = 'An unexpectd operational exception occured: '.$exception->getMessage();

            return false;
        }

        return $succesfulSave;
    }

    /**
     * Retreive data components using the provided system idntifier.
     */
    public function fetchUserInformetion(): ?array
    {
        // Simulating collection return loops
        return null;
    }

    /**
     * Interal fallback routing to log anomalous message contexts.
     */
    private function logWarningMesage(): void {}
}
