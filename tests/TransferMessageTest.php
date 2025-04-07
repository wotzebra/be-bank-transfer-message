<?php

use Wotz\BeBankTransferMessage\Exception\TransferMessageException;
use Wotz\BeBankTransferMessage\TransferMessage;

test('fails if number is not an int', function () {
    $transferMessage = new TransferMessage();
    $transferMessage->setNumber('abcd');
})->throws(TypeError::class);

test('fails if number is 0', function () {
    $transferMessage = new TransferMessage();
    $transferMessage->setNumber(0);
})->throws(TransferMessageException::class, 'The number should be an integer larger then 0 and smaller then 9999999999.');

test('fails if number is larger than 9999999999', function () {
    $transferMessage = new TransferMessage();
    $transferMessage->setNumber(10000000000);
})->throws(TransferMessageException::class, 'The number should be an integer larger then 0 and smaller then 9999999999.');

test('modulus getter', function () {
    $transferMessage = new TransferMessage(119698);

    expect($transferMessage->getModulus())->toEqual(TransferMessage::MODULO);

    $transferMessage->setNumber(123456);
    $transferMessage->generate();

    expect($transferMessage->getModulus())->toEqual(72);
});

test('number getter', function () {
    $expectedNumber = 119698;
    $transferMessage = new TransferMessage($expectedNumber);

    expect($transferMessage->getNumber())->toEqual($expectedNumber);
});

test('generated message format', function () {
    $pattern = '/^[\+\*]{3}[0-9]{3}[\/]?[0-9]{4}[\/]?[0-9]{5}[\+\*]{3}$/';

    $transferMessage = new TransferMessage();

    expect($transferMessage->generate())->toMatch($pattern);

    $transferMessage->generate(TransferMessage::CIRCUMFIX_ASTERISK);
    expect($transferMessage->generate())->toMatch($pattern);

    $transferMessage->setNumber(1);
    $transferMessage->generate();
    expect($transferMessage->generate())->toMatch($pattern);
});

test('structured message setter invalid input', function () {
    $transferMessage = new TransferMessage();
    $transferMessage->setStructuredMessage('+++000\0119\69897+++');
})->throws(TransferMessageException::class, 'The structured message does not have a valid format.');

test('validate structured message', function () {
    // Number with carry > 0
    $transferMessage = new TransferMessage(123456);
    expect($transferMessage->validate())->toBeTrue();

    // Number with carry = 0
    $transferMessage->setNumber(119698);
    $transferMessage->generate();
    expect($transferMessage->validate())->toBeTrue();

    // With 0's prepadded
    $transferMessage->setNumber(1);
    expect($transferMessage->validate())->toBeTrue();

    // Carry = 0
    $transferMessage->setStructuredMessage('+++000/0119/69897+++');
    expect($transferMessage->validate())->toBeTrue();

    // Carry > 0
    $transferMessage->setStructuredMessage('+++090/9337/55493+++');
    expect($transferMessage->validate())->toBeTrue();

    // With asterisks
    $transferMessage->setStructuredMessage('***090/9337/55493***');
    expect($transferMessage->validate())->toBeTrue();

    // Invalid structured message
    $transferMessage->setStructuredMessage('+++011/9337/55493+++');
    expect($transferMessage->validate())->toBeFalse();
});
