package deepcopy

func Map(src map[string]interface{}, dest map[string]interface{}) {
	for key, value := range src {
		switch src[key].(type) {
		case map[string]interface{}:
			dest[key] = map[string]interface{}{}
			Map(src[key].(map[string]interface{}), dest[key].(map[string]interface{}))
		default:
			dest[key] = value
		}
	}
}
