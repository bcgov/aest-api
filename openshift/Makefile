## get values from Github actions yml file. under env
export GIT_LOCAL_BRANCH?=$(shell git rev-parse --abbrev-ref HEAD)
export NAMESPACE := $(or $(NAMESPACE), 000000-dev)
export APP_NAME := $(or $(PROJECT_NAME), aest-api)
export BUILD_ID := $(or $(BUILD_ID), 1)
export BUILD_REF := $(or $(BUILD_REF), $(GIT_LOCAL_BRANCH))
export LICENSE_PLATE := $(or $(LICENSE_PLATE), 000000)
export LICENSE_BRANCH := $(or $(LICENSE_BRANCH), nonprod)
export BUILD_NAMESPACE := $(or $(BUILD_NAMESPACE), 000000-tools)
export DEPLOY_NAMESPACE := $(or $(DEPLOY_NAMESPACE), 000000-dev)
export DOMAIN := $(or $(DOMAIN), apps.silver.devops.gov.bc.ca)
export BC_NAME := $(or $(BC_NAME), aest-api)
export SOURCE_REPOSITORY_BRANCH := $(or $(SOURCE_REPOSITORY_BRANCH), dev)
export OC_USER_ID := $(or $(OC_USER_ID), 1000000000)
export DEPLOY_FILE := $(or $(DEPLOY_FILE), dc_latest.yaml)
export BUILD_TAG := $(or $(BUILD_TAG), latest)

define PARAM_FILE
$(GIT_LOCAL_BRANCH)".env"
endef


#define general variables
define LABEL_NAME
"API"
endef


define SOURCE_REPOSITORY_REF
"https://github.com/bcgov/aest-api"
endef


define rollout_and_wait
@oc -n $(DEPLOY_NAMESPACE) rollout status $1
endef

oc-build-api: | print-status build-api
oc-deploy-api: | print-status deploy-api

print-status:
	@echo " +---------------------------------------------------------+ "
	@echo " | Current Settings										| "
	@echo " +---------------------------------------------------------+ "
	@echo " | PROJECT:	  			$(APP_NAME)"
	@echo " | BRANCH:	   			$(BUILD_REF)"
	@echo " | NAMESPACE:					$(NAMESPACE)"
	@echo " | BUILD_NAMESPACE:				$(BUILD_NAMESPACE)"
	@echo " | GIT_LOCAL_BRANCH:				$(GIT_LOCAL_BRANCH)"
	@echo " +---------------------------------------------------------+ "
	@echo " | BUILD_ID:						$(BUILD_ID) |"
	@echo " | BUILD_TAG:						$(BUILD_TAG) |"
	@echo " | REPO_NAME: 		$(SOURCE_REPOSITORY_REF) |"
	@echo " | REPO_BRANCH:						$(SOURCE_REPOSITORY_BRANCH) |"
	@echo " | OC_USER_ID: 					$(OC_USER_ID) |"
	@echo " | PARAM_FILE: 					$(PARAM_FILE) |"
	@echo " | PARAM_FILE: 					$(PARAM_FILE) |"
	@echo " | LICENSE_PLATE: 				$(LICENSE_PLATE) |"

	@echo " +---------------------------------------------------------+ "

build-api:
	test -n "$(BUILD_REF)"
	test -n "$(BUILD_NAMESPACE)"
	@echo "+\n++ PROCESSING API\n+"
	@oc -n $(BUILD_NAMESPACE) process -f bc.yaml --param-file=$(PARAM_FILE) | oc -n $(BUILD_NAMESPACE) apply -f -
	@echo "+\n++ BUILDING API\n+"
	@oc -n $(BUILD_NAMESPACE) start-build bc/$(BC_NAME) --wait
	@echo "+\n++ FINISHED BUILDING API\n+"

deploy-web:
	@echo "+\n++ START DEPLOYING API\n+"
	test -n "$(DEPLOY_NAMESPACE)"
	test -n "$(BUILD_NAMESPACE)"
	@echo "+\n++ Deploying API into $(NAMESPACE)\n+"
	@oc -n $(DEPLOY_NAMESPACE) process -f $(DEPLOY_FILE) --param-file=$(PARAM_FILE) | oc -n $(DEPLOY_NAMESPACE) apply -f -
	$(call rollout_and_wait,dc/$(BC_NAME))
	@echo "+\n++ FINISHED Deploying API\n+"
