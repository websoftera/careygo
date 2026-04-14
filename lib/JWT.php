<?php
/**
 * Pure-PHP JWT helper — no external dependencies.
 * Supports: HS256
 */
class JWT
{
    // ---------------------------------------------------------------
    // Encode
    // ---------------------------------------------------------------
    public static function encode(array $payload, string $secret, string $algo = 'HS256'): string
    {
        $header  = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => $algo]));
        $payload = self::base64UrlEncode(json_encode($payload));
        $sig     = self::base64UrlEncode(self::sign("$header.$payload", $secret, $algo));
        return "$header.$payload.$sig";
    }

    // ---------------------------------------------------------------
    // Decode — throws RuntimeException on failure
    // ---------------------------------------------------------------
    public static function decode(string $token, string $secret, string $algo = 'HS256'): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token structure');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $expectedSig = self::base64UrlEncode(self::sign("$headerB64.$payloadB64", $secret, $algo));
        if (!hash_equals($expectedSig, $sigB64)) {
            throw new RuntimeException('Invalid token signature');
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid token payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new RuntimeException('Token has expired');
        }

        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            throw new RuntimeException('Token not yet valid');
        }

        return $payload;
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private static function sign(string $data, string $secret, string $algo): string
    {
        $map = ['HS256' => 'sha256', 'HS384' => 'sha384', 'HS512' => 'sha512'];
        if (!isset($map[$algo])) {
            throw new RuntimeException("Unsupported algorithm: $algo");
        }
        return hash_hmac($map[$algo], $data, $secret, true);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $pad  = strlen($data) % 4;
        if ($pad) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
