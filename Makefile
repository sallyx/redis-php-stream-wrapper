.PHONY: test

test:
	./vendor/bin/tester -c tests/php-unix.ini tests/wrapper/
