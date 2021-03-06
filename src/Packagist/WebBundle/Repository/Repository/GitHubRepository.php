<?php

namespace Packagist\WebBundle\Repository\Repository;

class GitHubRepository implements RepositoryInterface
{
    protected $owner;
    protected $repository;
    protected $repositoryData;
    protected $tags;
    protected $branches;
    protected $infoCache = array();

    public function __construct($url)
    {
        preg_match('#^(?:https?|git)://github\.com/([^/]+)/(.+?)(?:\.git)?$#', $url, $match);
        $this->owner = $match[1];
        $this->repository = $match[2];
    }

    /**
     * {@inheritDoc}
     */
    public function getType()
    {
        return 'git';
    }

    /**
     * {@inheritDoc}
     */
    public function getRootIdentifier()
    {
        return 'master';
    }

    /**
     * {@inheritDoc}
     */
    public function getUrl()
    {
        return 'http://github.com/'.$this->owner.'/'.$this->repository.'.git';
    }

    /**
     * {@inheritDoc}
     */
    public function getSource($identifier)
    {
        $label = array_search($identifier, (array) $this->tags) ?: $identifier;

        return array('type' => $this->getType(), 'url' => $this->getUrl(), 'reference' => $label, 'shasum' => '');
    }

    /**
     * {@inheritDoc}
     */
    public function getDist($identifier)
    {
        $repoData = $this->getRepositoryData();
        $attempts = 3;

        while ($attempts--) {
            $label = array_search($identifier, (array) $this->tags) ?: array_search($identifier, (array) $this->branches) ?: $identifier;
            $url = 'https://github.com/'.$this->owner.'/'.$this->repository.'/zipball/'.$label;
            if (!$checksum = @hash_file('sha1', $url)) {
                continue;
            }

            return array('type' => 'zip', 'url' => $url, 'shasum' => $checksum, 'reference' => $label);
        }

        // TODO clone the repo and build/host a zip ourselves. Not sure if this can happen, but it'll be needed for non-GitHub repos anyway
        throw new \LogicException('Could not retrieve dist file');
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerInformation($identifier)
    {
        if (!isset($this->infoCache[$identifier])) {
            $composer = json_decode(@file_get_contents('https://raw.github.com/'.$this->owner.'/'.$this->repository.'/'.$identifier.'/composer.json'), true);
            if (!$composer) {
                throw new \UnexpectedValueException('Failed to download retrieve composer information for identifier '.$identifier.' in '.$this->getUrl());
            }

            if (!isset($composer['time'])) {
                $commit = json_decode(file_get_contents('http://github.com/api/v2/json/commits/show/'.$this->owner.'/'.$this->repository.'/'.$identifier), true);
                $composer['time'] = $commit['commit']['committed_date'];
            }
            $this->infoCache[$identifier] = $composer;
        }

        return $this->infoCache[$identifier];
    }

    /**
     * {@inheritDoc}
     */
    public function getTags()
    {
        if (null === $this->tags) {
            $tagsData = json_decode(file_get_contents('http://github.com/api/v2/json/repos/show/'.$this->owner.'/'.$this->repository.'/tags'), true);
            $this->tags = $tagsData['tags'];
        }

        return $this->tags;
    }

    /**
     * {@inheritDoc}
     */
    public function getBranches()
    {
        if (null === $this->branches) {
            $branchesData = json_decode(file_get_contents('http://github.com/api/v2/json/repos/show/'.$this->owner.'/'.$this->repository.'/branches'), true);
            $this->branches = $branchesData['branches'];
        }

        return $this->branches;
    }

    /**
     * {@inheritDoc}
     */
    public function hasComposerFile($identifier)
    {
        return (false !== @fopen('https://raw.github.com/'.$this->owner.'/'.$this->repository.'/'.$identifier.'/composer.json', 'r'));
    }

    protected function getRepositoryData()
    {
        if (null === $this->repositoryData) {
            $url = 'http://github.com/api/v2/json/repos/show/'.$this->owner.'/'.$this->repository;
            $this->repositoryData = json_decode(@file_get_contents($url), true);
            if (!$this->repositoryData) {
                throw new \UnexpectedValueException('Failed to download from '.$url);
            }
        }

        return $this->repositoryData;
    }
}
