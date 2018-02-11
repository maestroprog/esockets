# EasyStream work with php sockets, using callback handlers

## What is esockets?

This project was conceived as a tool to facilitate the work with inter-process communication.

## Which PHP version is supported?

Supported PHP 7.1 or higher version

## Samples of use:
* [A simple example of working server and client in blocking mode](sample/index-wiki.php)
* [An example of a simple HTTP server](sample/http/server.php)

## Features

* Callback handlers for all socket events,
* Built-in application protocol `EasyStream`, allowing to transfer on a network practically any data structures of php (only for tcp),
* Supports blocking and non-blocking modes of operation
* Correct work on Linux and Windows

