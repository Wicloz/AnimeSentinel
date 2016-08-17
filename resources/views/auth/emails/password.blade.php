Click here to reset your password: <a href="{{ $link = fullUrl('/password/reset', $token).'?email='.urlencode($user->getEmailForPasswordReset()) }}"> {{ $link }} </a>
