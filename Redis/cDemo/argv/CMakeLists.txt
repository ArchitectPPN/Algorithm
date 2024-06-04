cmake_minimum_required(VERSION 3.22)
project(argv C)

set(CMAKE_C_STANDARD 11)

include_directories(..)

add_executable(argv
        main.c)
