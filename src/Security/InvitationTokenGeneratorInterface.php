<?php

namespace App\Security;

interface invitationTokenGeneratorInterface {
    
    public function generate(int $bytes = 32): string;

}