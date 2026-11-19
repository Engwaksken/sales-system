/*
 * Passkeys (WebAuthn): sign in with the device's fingerprint, face or PIN.
 * Talks to /passkeys/... routes; binary fields travel as base64url strings.
 */
(function () {
    'use strict';

    function toBytes(base64url) {
        var base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        var padded = base64 + '==='.slice((base64.length + 3) % 4);
        var raw = atob(padded);
        var bytes = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) bytes[i] = raw.charCodeAt(i);
        return bytes.buffer;
    }

    function toBase64url(buffer) {
        var bytes = new Uint8Array(buffer);
        var raw = '';
        for (var i = 0; i < bytes.length; i++) raw += String.fromCharCode(bytes[i]);
        return btoa(raw).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body || {})
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) {
                    var error = new Error(serverMessage(response.status, data));
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        });
    }

    function serverMessage(status, data) {
        if (status === 419) return 'This page expired. Refresh it and try again.';
        if (status === 429) return 'Too many attempts. Please wait a minute and try again.';
        if (data && data.message && status < 500) return data.message;
        return 'Something went wrong. Please try again.';
    }

    /** Plain-language text for errors the browser raises, or null for a deliberate cancel. */
    function deviceMessage(error) {
        var name = error && error.name;
        if (name === 'NotAllowedError' || name === 'AbortError') return null;
        if (name === 'InvalidStateError') return 'This device already has a passkey for your account.';
        if (name === 'SecurityError') return 'Passkeys only work on the secure (https) address of this site.';
        if (name === 'NotSupportedError') return 'This device or browser cannot make a passkey.';
        return error && error.message ? error.message : 'Something went wrong. Please try again.';
    }

    function creationOptions(json) {
        var options = Object.assign({}, json);
        options.challenge = toBytes(json.challenge);
        options.user = Object.assign({}, json.user, { id: toBytes(json.user.id) });
        options.excludeCredentials = (json.excludeCredentials || []).map(function (c) {
            return Object.assign({}, c, { id: toBytes(c.id) });
        });
        return options;
    }

    function requestOptions(json) {
        var options = Object.assign({}, json);
        options.challenge = toBytes(json.challenge);
        if (json.allowCredentials) {
            options.allowCredentials = json.allowCredentials.map(function (c) {
                return Object.assign({}, c, { id: toBytes(c.id) });
            });
        }
        return options;
    }

    var Passkeys = {
        supported: function () {
            return !!(window.PublicKeyCredential && navigator.credentials && navigator.credentials.create);
        },

        /** True when the device has a built-in fingerprint, face or PIN check. */
        platformAvailable: function () {
            if (!Passkeys.supported() || !PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable) {
                return Promise.resolve(false);
            }
            return PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable().catch(function () { return false; });
        },

        /** Adds a passkey for the signed-in user. Resolves to {ok, message}. */
        register: function (alias) {
            return post('/passkeys/options')
                .then(function (json) {
                    return navigator.credentials.create({ publicKey: creationOptions(json) });
                })
                .then(function (credential) {
                    return post('/passkeys', {
                        id: credential.id,
                        rawId: toBase64url(credential.rawId),
                        type: credential.type,
                        alias: alias || '',
                        response: {
                            clientDataJSON: toBase64url(credential.response.clientDataJSON),
                            attestationObject: toBase64url(credential.response.attestationObject),
                            transports: credential.response.getTransports ? credential.response.getTransports() : []
                        }
                    });
                })
                .then(function (data) { return { ok: true, message: data.message }; })
                .catch(function (error) { return { ok: false, message: error.status ? error.message : deviceMessage(error) }; });
        },

        /** Signs in with any passkey on this device. Resolves to {ok, redirect, message}. */
        login: function (remember) {
            return post('/passkeys/login/options')
                .then(function (json) {
                    return navigator.credentials.get({ publicKey: requestOptions(json) });
                })
                .then(function (assertion) {
                    var r = assertion.response;
                    return post('/passkeys/login', {
                        id: assertion.id,
                        rawId: toBase64url(assertion.rawId),
                        type: assertion.type,
                        remember: remember ? 1 : '',
                        response: {
                            authenticatorData: toBase64url(r.authenticatorData),
                            clientDataJSON: toBase64url(r.clientDataJSON),
                            signature: toBase64url(r.signature),
                            userHandle: r.userHandle ? toBase64url(r.userHandle) : null
                        }
                    });
                })
                .then(function (data) { return { ok: true, redirect: data.redirect }; })
                .catch(function (error) { return { ok: false, message: error.status ? error.message : deviceMessage(error) }; });
        }
    };

    window.Passkeys = Passkeys;
})();
