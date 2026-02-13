<?php

namespace App\Security;

final class invitationTokenGenerator implements invitationTokenGeneratorInterface
{
    
    public function generate(int $bytes = 32): string {

        return bin2hex(random_bytes($bytes));
    }

}