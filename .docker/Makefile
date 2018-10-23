# This file is part of the Kimai time-tracking app.
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

NAME = tobybatch/kimai
BRANCH = $(shell git rev-parse --abbrev-ref HEAD)
VERSION = $(subst /,_,$(BRANCH))

test:
	env NAME=$(NAME) VERSION=$(VERSION) bats --tap tests.bats

build:
	docker build -t $(NAME):$(VERSION) --rm ..

build-nocache:
	docker build -t $(NAME):$(VERSION) --rm --no-cache ..

tag-latest:
	docker tag $(NAME):$(VERSION) $(NAME):latest

push:
	docker push $(NAME):$(VERSION)

release: build test tag-latest push

info:
	@echo Branch is $(BRANCH)
	@echo Version is $(VERSION)
