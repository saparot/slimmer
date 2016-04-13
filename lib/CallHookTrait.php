<?php
    
    namespace Slimmer;
    
    use Creator\Creator;

    trait CallHookTrait {

        /**
         * @var callable[]
         */
        protected $registeredHooks = [];

        /**
         * @return Creator
         */
        abstract function getCreator();

        /**
         * @param string $hook
         *
         * @return bool
         */
        function supportsHook ($hook) {
            return (isset($this->registeredHooks[$hook]) || method_exists($this, $this->getHookMethodName($hook)));
        }

        /**
         * @param string $hook
         * @param Response $response
         *
         * @return Response
         * @throws \Exception
         */
        function callHook($hook, Response $response) {
            if ($this->callHookFromRegistry($hook, $response) === null) {
                $this->callHookFromClassBody($hook, $response);
            }

            return $response;
        }

        /**
         * @param string $hook
         * @param callable $callable
         *
         * @return $this
         */
        function registerHook ($hook, callable $callable) {
            if (!isset($this->registeredHooks[$hook])) {
                $this->registeredHooks[$hook] = [];
            }

            $this->registeredHooks[$hook][] = $callable;

            return $this;
        }

        /**
         * @param string $hook
         * @param Response $response
         *
         * @return Response
         * @throws \Exception
         */
        private function callHookFromRegistry ($hook, Response $response) {
            if (!isset($this->registeredHooks[$hook])) {
                return null;
            }

            foreach ($this->registeredHooks[$hook] as $hookCallable) {
                $this->callHookWithCallable($hookCallable, $response);
            }

            return $response;
        }

        /**
         * @param string $hook
         * @param Response $response
         *
         * @return $this|null
         */
        private function callHookFromClassBody ($hook, Response $response) {
            $hookMethodName = $this->getHookMethodName($hook);
            if (!method_exists($this, $hookMethodName)) {
                return null;
            }

            return $this->callHookWithCallable([$this, $hookMethodName], $response);
        }

        /**
         * @param string $hook
         *
         * @return mixed
         */
        private function getHookMethodName ($hook) {
            return sprintf('%sHook', $hook);
        }

        /**
         * @param callable $hookCallable
         * @param Response $response
         *
         * @return $this
         * @throws \Exception
         */
        private function callHookWithCallable (callable $hookCallable, Response $response) {
            $hookInvokable = $this->getCreator()
                ->invokeInjected($hookCallable)
                ->with($response)
                ->with($response->getBuffer());

            $hookResultBuffer = new Buffer($hookInvokable->invoke());

            return $response->getBuffer()->mergeContents($hookResultBuffer);
        }

    }