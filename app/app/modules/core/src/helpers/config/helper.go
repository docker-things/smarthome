package config

import (
	"path"
	"strings"
)

func filenameWithoutExt(filename string) string {
	return strings.TrimSuffix(filename, path.Ext(filename))
}

func getConfigTreeFromPath(filepath string) []string {
	tree := strings.Split(filepath, "/")[4:]
	tree[len(tree)-1] = filenameWithoutExt(tree[len(tree)-1])
	return tree
}
