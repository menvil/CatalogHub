@extends('errors.application-layout')

@section('title', 'Request could not be completed')
@section('status', $status)
@section('heading', 'We could not complete this request')
@section('message', $message)
