## get values from Github actions yml file. under env
export GIT_LOCAL_BRANCH?=$(shell git rev-parse --abbrev-ref HEAD)
export APP_NAME := $(or $(PROJECT_NAME), aest-api)
export BUILD_ID := $(or $(BUILD_ID), 1)
export BUILD_REF := $(or $(BUILD_REF), $(GIT_LOCAL_BRANCH))
export BUILD_NAMESPACE := $(or $(BUILD_NAMESPACE), 000000-tools)
export DEPLOY_NAMESPACE := $(or $(DEPLOY_NAMESPACE), 000000-dev)
export BC_NAME := $(or $(BC_NAME), aest-api)
export BUILD_FILE := $(or $(BUILD_FILE), bc.yaml)
export DEPLOY_FILE := $(or $(DEPLOY_FILE), dc_latest.yaml)
export BUILD_TAG := $(or $(BUILD_TAG), latest)

define PARAM_FILE
$(GIT_LOCAL_BRANCH)".env"
endef


#define general variables
define LABEL_NAME
"API"
endef


define rollout_and_wait
@kubectl -n $(DEPLOY_NAMESPACE) rollout status $1
endef

oc-build-api: | print-status build-api
oc-deploy-api: | print-status deploy-api

print-status:
	@echo " +---------------------------------------------------------+ "
	@echo " | Current Settings										| "
	@echo " +---------------------------------------------------------+ "
	@echo " | PROJECT:	  			$(APP_NAME)"
	@echo " | BRANCH:	   			$(BUILD_REF)"
	@echo " | BUILD_NAMESPACE:				$(BUILD_NAMESPACE)"
	@echo " | GIT_LOCAL_BRANCH:				$(GIT_LOCAL_BRANCH)"
	@echo " +---------------------------------------------------------+ "
	@echo " | BUILD_ID:						$(BUILD_ID) |"
	@echo " | BUILD_TAG:						$(BUILD_TAG) |"
	@echo " | PARAM_FILE: 					$(PARAM_FILE) |"

	@echo " +---------------------------------------------------------+ "

build-api:
	test -n "$(BUILD_REF)"
	test -n "$(BUILD_NAMESPACE)"
	@echo "+\n++ PROCESSING API\n+"
	@kubectl -n $(BUILD_NAMESPACE) apply -f $(BUILD_FILE)
	@echo "+\n++ BUILDING API\n+"
	@kubectl -n $(BUILD_NAMESPACE) start-build $(BC_NAME) --wait
	@echo "+\n++ FINISHED BUILDING API\n+"

deploy-api:
	@echo "+\n++ START DEPLOYING API\n+"
	test -n "$(DEPLOY_NAMESPACE)"
	test -n "$(BUILD_NAMESPACE)"
	@echo "+\n++ Deploying API into $(DEPLOY_NAMESPACE)\n+"
	@kubectl -n $(DEPLOY_NAMESPACE) apply -f $(DEPLOY_FILE)
	$(call rollout_and_wait,deployment/$(BC_NAME))
	@echo "+\n++ FINISHED Deploying API\n+"

